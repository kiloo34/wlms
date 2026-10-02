<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixMigrationsTableSequence extends Command
{
    protected $signature = 'db:fix-migrations';

    protected $description = 'Fix missing/out-of-sync sequences on PostgreSQL tables';

    public function handle(): int
    {
        $this->info('Fixing sequences...');

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($driver !== 'pgsql') {
                $this->warn('This fix is only for PostgreSQL. Current driver: ' . $driver);
                return 0;
            }

            // Fix Migrations specifically (in case sequence is missing)
            $connection->statement("CREATE SEQUENCE IF NOT EXISTS migrations_id_seq;");
            $connection->statement("ALTER TABLE migrations ALTER COLUMN id SET DEFAULT nextval('migrations_id_seq');");

            // Tables to sync
            $tables = ['migrations', 'users', 'workspace_members', 'jobs', 'failed_jobs'];

            foreach ($tables as $t) {
                if (Schema::hasTable($t)) {
                    $this->info("Syncing sequence for {$t}...");
                    // Safely get max ID and sync
                    $connection->statement("
                        SELECT setval(
                            COALESCE(pg_get_serial_sequence('{$t}', 'id'), '{$t}_id_seq'),
                            COALESCE((SELECT MAX(id) FROM {$t}), 1)
                        );
                    ");
                }
            }

            $this->info('Successfully fixed sequences!');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
