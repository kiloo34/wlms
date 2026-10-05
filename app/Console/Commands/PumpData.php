<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PumpData extends Command
{
    protected $signature = 'db:pump';

    protected $description = 'Pump data from local SQLite to remote PostgreSQL';

    public function handle(): void
    {
        $this->info('Starting data pump from SQLite to PostgreSQL...');

        $tables = [
            'users',
            'org_levels',
            'org_units',
            'org_unit_closures',
            'roles',
            'permissions',
            'menus',
            'role_permissions',
            'role_menus',
            'user_roles',
            'workspaces',
            'workspace_members',
            'workflows',
            'statuses',
            'workflow_transitions',
            'projects',
            'sprints',
            'issue_types',
            'priorities',
            'issue_link_types',
            'issues',
            'comments',
            'issue_comments',
            'issue_histories',
            'worklogs',
            'issue_links',
            'audit_logs',
            'notifications',
        ];

        $this->info('Clearing existing data in target database (Reverse Order)...');
        foreach (array_reverse($tables) as $table) {
            if (Schema::connection('pgsql')->hasTable($table)) {
                try {
                    DB::connection('pgsql')->table($table)->delete();
                } catch (\Exception $e) {
                    $this->warn("Failed to delete from {$table}: ".$e->getMessage());
                }
            }
        }

        foreach ($tables as $table) {
            if (! Schema::connection('sqlite')->hasTable($table)) {
                continue;
            }

            $count = DB::connection('sqlite')->table($table)->count();
            if ($count === 0) {
                continue;
            }

            $this->info("Pumping table: {$table} ({$count} rows)");

            $query = DB::connection('sqlite')->table($table);

            if (Schema::connection('sqlite')->hasColumn($table, 'parent_id')) {
                $query->orderBy('parent_id', 'asc');
            } elseif ($table === 'issue_links') {
                $query->orderBy('source_issue_id', 'asc');
            } elseif ($table === 'org_unit_closures') {
                $query->orderBy('ancestor_id', 'asc');
            } elseif (Schema::connection('sqlite')->hasColumn($table, 'id')) {
                $query->orderBy('id', 'asc');
            } else {
                $columns = Schema::connection('sqlite')->getColumnListing($table);
                if (! empty($columns)) {
                    $query->orderBy($columns[0], 'asc');
                }
            }

            $query->chunk(500, function ($rows) use ($table) {
                $insertData = [];
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    foreach ($rowArray as $col => $val) {
                        // 1. Cast boolean
                        if (in_array($col, ['is_active', 'is_default', 'is_edited'])) {
                            $rowArray[$col] = $val ? true : false;
                        }

                        // 2. Fix old invalid dummy UUIDs on the fly
                        if (is_string($val)) {
                            if ($val === '01923abc-org-1234-1234-123456789abc') {
                                $rowArray[$col] = '01923abc-1111-1234-1234-123456789abc';
                            }
                            if ($val === '01923abc-level-1234-1234-123456789abc') {
                                $rowArray[$col] = '01923abc-0000-1234-1234-123456789abc';
                            }
                        }
                    }
                    $insertData[] = $rowArray;
                }

                if (! empty($insertData)) {
                    try {
                        DB::connection('pgsql')->table($table)->insert($insertData);
                    } catch (\Exception $e) {
                        $this->error("Error inserting into {$table}: ".$e->getMessage());
                        throw $e;
                    }
                }
            });
        }

        $this->info('Fixing sequences...');
        $tablesWithId = ['users', 'workspace_members', 'jobs', 'failed_jobs', 'migrations'];
        foreach ($tablesWithId as $t) {
            if (Schema::connection('pgsql')->hasTable($t)) {
                try {
                    $max = DB::connection('pgsql')->table($t)->max('id') ?? 0;
                    if ($max > 0) {
                        DB::connection('pgsql')->statement("SELECT setval('{$t}_id_seq', {$max} + 1, false);");
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $this->info('Data pump complete!');
    }
}
