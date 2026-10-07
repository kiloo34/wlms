<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex(['lead_id']);
            });

            DB::statement('ALTER TABLE projects DROP COLUMN lead_id');
            DB::statement('ALTER TABLE projects ADD COLUMN lead_id bigint NULL');
            
            Schema::table('projects', function (Blueprint $table) {
                $table->index('lead_id');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex(['lead_id']);
            });

            DB::statement('ALTER TABLE projects DROP COLUMN lead_id');
            DB::statement('ALTER TABLE projects ADD COLUMN lead_id uuid NULL');
            
            Schema::table('projects', function (Blueprint $table) {
                $table->index('lead_id');
            });
        }
    }
};
