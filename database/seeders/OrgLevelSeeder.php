<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OrgLevelSeeder
 *
 * Mengisi data awal konfigurasi hierarki 5 level organisasi.
 * INI ADALAH DATA KONFIGURASI, bukan data bisnis.
 * Seeder ini IDEMPOTENT: aman dijalankan berulang kali (menggunakan upsert).
 */
class OrgLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Direksi',
                'slug' => 'direksi',
                'depth' => 1,
                'is_leaf' => false,
                'can_own_workspace' => false,
            ],
            [
                'name' => 'SEVP',
                'slug' => 'sevp',
                'depth' => 2,
                'is_leaf' => false,
                'can_own_workspace' => false,
            ],
            [
                'name' => 'VP',
                'slug' => 'vp',
                'depth' => 3,
                'is_leaf' => false,
                'can_own_workspace' => false,
            ],
            [
                'name' => 'Sub-Divisi',
                'slug' => 'subdiv',
                'depth' => 4,
                'is_leaf' => false,
                'can_own_workspace' => false,
            ],
            [
                'name' => 'Group',
                'slug' => 'group',
                'depth' => 5,
                'is_leaf' => true,
                'can_own_workspace' => true, // HANYA level ini yang bisa punya Workspace
            ],
        ];

        foreach ($levels as $level) {
            DB::table('org_levels')->upsert(
                array_merge($level, [
                    'id' => (string) Str::uuid(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                ['slug'],                          // Conflict key
                ['name', 'depth', 'is_leaf', 'can_own_workspace', 'updated_at'] // Update jika conflict
            );
        }
    }
}
