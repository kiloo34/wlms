<?php

namespace Database\Seeders;

use App\Modules\Identity\Infrastructure\Database\Seeders\MenuAndPermissionSeeder;
use App\Modules\Identity\Infrastructure\Database\Seeders\RbacSimulationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RbacSimulationSeeder::class,
            MenuAndPermissionSeeder::class,
            WorkloadLookupSeeder::class,
            KnowledgeBaseSeeder::class,
        ]);
    }
}
