<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrationsTableSequence extends Command
{
    protected $signature = 'db:fix-migrations';

    protected $description = 'Fix missing sequence on migrations table ID column for PostgreSQL';

    public function handle(): int
    {
        $this->info('Fixing migrations table sequence...');

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if ($driver !== 'pgsql') {
                $this->warn('This fix is only for PostgreSQL. Current driver: ' . $driver);
                return 0;
            }

            // 1. Buat sequence jika belum ada
            $connection->statement("CREATE SEQUENCE IF NOT EXISTS migrations_id_seq;");

            // 2. Pasang sequence sebagai default value pada kolom id
            $connection->statement("ALTER TABLE migrations ALTER COLUMN id SET DEFAULT nextval('migrations_id_seq');");

            // 3. Sinkronisasi nilai sequence ke MAX(id) yang ada agar tidak bentrok
            $connection->statement("SELECT setval('migrations_id_seq', COALESCE((SELECT MAX(id) FROM migrations), 1));");

            $this->info('Successfully fixed migrations table sequence!');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
