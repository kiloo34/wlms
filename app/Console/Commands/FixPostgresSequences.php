<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPostgresSequences extends Command
{
    protected $signature = 'db:fix-sequences';
    protected $description = 'Fix PostgreSQL auto-increment sequences after data import';

    public function handle(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            $this->error("This command is only for PostgreSQL. Current driver: {$driver}");
            return;
        }

        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        
        foreach ($tables as $tableObj) {
            $table = $tableObj->tablename;
            
            // Periksa apakah tabel punya kolom 'id' yang serial/auto-increment
            $hasSerial = DB::select("SELECT column_default FROM information_schema.columns WHERE table_name = ? AND column_name = 'id'", [$table]);
            
            if (!empty($hasSerial) && strpos($hasSerial[0]->column_default ?? '', 'nextval') !== false) {
                try {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), coalesce(max(id), 0) + 1, false) FROM \"{$table}\";");
                    $this->info("✔ Sequence fixed for table: {$table}");
                } catch (\Exception $e) {
                    $this->warn("⚠ Could not fix sequence for {$table}");
                }
            }
        }
        
        $this->info('All sequences resynced successfully!');
    }
}
