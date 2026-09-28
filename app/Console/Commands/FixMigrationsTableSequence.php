<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:fix-migrations')]
#[Description('Fix missing sequence on migrations table ID column for PostgreSQL')]
class FixMigrationsTableSequence extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing migrations table sequence...');
        
        try {
            $connection = \Illuminate\Support\Facades\DB::connection();
            $driver = $connection->getDriverName();
            
            if ($driver !== 'pgsql') {
                $this->warn('This fix is only for PostgreSQL databases. Current driver: ' . $driver);
                return 0;
            }

            // 1. Buat sequence jika belum ada
            $connection->statement("CREATE SEQUENCE IF NOT EXISTS migrations_id_seq;");
            
            // 2. Set nilai default ID pada tabel migrations menggunakan sequence tersebut
            $connection->statement("ALTER TABLE migrations ALTER COLUMN id SET DEFAULT nextval('migrations_id_seq');");
            
            // 3. Sinkronisasi nilai maksimum dari tabel ke sequence agar tidak terjadi bentrok ID
            $connection->statement("SELECT setval('migrations_id_seq', COALESCE((SELECT MAX(id) FROM migrations), 1));");

            $this->info('Successfully fixed migrations table sequence!');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to fix sequence: ' . $e->getMessage());
            return 1;
        }
    }
}
