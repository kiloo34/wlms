<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mengisi Katalog Dashboard Tableau dari database/data/tableau_dashboards.php.
 * Idempotent: baris dicocokkan berdasarkan nomor + nama, jadi aman dijalankan ulang.
 */
class TableauDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $inserted = self::seedFromCatalog();

        $this->command->info("Tableau dashboard catalog seeded ({$inserted} new rows).");
    }

    /**
     * Dipakai juga oleh migrasi seed production agar logikanya satu sumber.
     *
     * @return int jumlah baris baru yang ditambahkan
     */
    public static function seedFromCatalog(): int
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require database_path('data/tableau_dashboards.php');

        $inserted = 0;
        $now = now();

        foreach ($rows as $row) {
            $exists = DB::table('tableau_dashboards')
                ->where('number', $row['number'])
                ->where('name', $row['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('tableau_dashboards')->insert([
                'id' => Str::uuid()->toString(),
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return $inserted;
    }
}
