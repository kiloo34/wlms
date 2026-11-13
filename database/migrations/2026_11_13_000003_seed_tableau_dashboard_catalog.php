<?php

declare(strict_types=1);

use Database\Seeders\TableauDashboardSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Mengisi katalog dengan 159 dashboard existing dari sheet Daily Check Tableau
 * agar production langsung terisi tanpa perlu menjalankan seeder manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        TableauDashboardSeeder::seedFromCatalog();
    }

    public function down(): void
    {
        // Data katalog dipertahankan (Zero Data Loss)
    }
};
