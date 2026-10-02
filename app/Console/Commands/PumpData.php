<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PumpData extends Command
{
    protected $signature = 'db:pump';
    protected $description = 'Pump data from local SQLite to remote PostgreSQL';

    public function handle()
    {
        $this->info('Starting data pump from SQLite to PostgreSQL...');

        // Urutan tabel dari INDUK (Parent) ke ANAK (Child) agar Foreign Key tidak error
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

        // 1. Bersihkan tabel (Reverse Order agar anak dihapus duluan)
        $this->info('Clearing existing data in target database (Reverse Order)...');
        foreach (array_reverse($tables) as $table) {
            if (Schema::connection('pgsql')->hasTable($table)) {
                try {
                    DB::connection('pgsql')->table($table)->delete();
                } catch (\Exception $e) {
                    $this->warn("Failed to delete from {$table}: " . $e->getMessage());
                }
            }
        }

        // 2. Insert data (Topological Order)
        foreach ($tables as $table) {
            if (!Schema::connection('sqlite')->hasTable($table)) {
                continue;
            }

            $count = DB::connection('sqlite')->table($table)->count();
            if ($count === 0) continue;

            $this->info("Pumping table: {$table} ({$count} rows)");
            
            $query = DB::connection('sqlite')->table($table);
            
            // Handle self-referencing tables (parent_id) so parents are inserted before children
            if (Schema::connection('sqlite')->hasColumn($table, 'parent_id')) {
                // SQLite puts NULLs first when sorting ASC
                $query->orderBy('parent_id', 'asc');
            }
            // Fix issues self reference if any (like source_issue_id)
            if ($table === 'issue_links') {
                $query->orderBy('source_issue_id', 'asc');
            }
            if ($table === 'org_unit_closures') {
                $query->orderBy('ancestor_id', 'asc');
            }

            $query->chunk(500, function ($rows) use ($table) {
                $insertData = [];
                foreach ($rows as $row) {
                    // Convert integer to true/false for known boolean columns
                    // PostgreSQL is strict about boolean types.
                    $rowArray = (array) $row;
                    foreach ($rowArray as $col => $val) {
                        if (in_array($col, ['is_active', 'is_default', 'is_edited'])) {
                            $rowArray[$col] = $val ? true : false;
                        }
                    }
                    $insertData[] = $rowArray;
                }
                
                if (!empty($insertData)) {
                    try {
                        DB::connection('pgsql')->table($table)->insert($insertData);
                    } catch (\Exception $e) {
                        $this->error("Error inserting into {$table}: " . $e->getMessage());
                        // Stop execution on first error to avoid cascade of errors
                        throw $e;
                    }
                }
            });
        }

        // 3. Fix sequences for tables with auto-increment
        $this->info("Fixing sequences...");
        $tablesWithId = ['users', 'workspace_members', 'jobs', 'failed_jobs', 'migrations'];
        foreach ($tablesWithId as $t) {
            if (Schema::connection('pgsql')->hasTable($t)) {
                try {
                    $max = DB::connection('pgsql')->table($t)->max('id') ?? 0;
                    if ($max > 0) {
                        DB::connection('pgsql')->statement("SELECT setval('{$t}_id_seq', {$max} + 1, false);");
                    }
                } catch (\Exception $e) {
                    // Abaikan jika sequence tidak ada
                }
            }
        }

        $this->info('Data pump complete! Silakan periksa aplikasi Anda.');
    }
}
