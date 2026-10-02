<?php

namespace App\Modules\Identity\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;

class DummySeeder extends Seeder
{
    public function run()
    {
        if (DB::table('org_levels')->count() === 0) {
            DB::table('org_levels')->insert([
                'id' => '01923abc-0000-1234-1234-123456789abc',
                'slug' => 'group',
                'name' => 'Group',
                'depth' => 1,
                'can_own_workspace' => true
            ]);
        }

        if (DB::table('org_units')->count() === 0) {
            DB::table('org_units')->insert([
                'id' => '01923abc-1111-1234-1234-123456789abc',
                'org_level_id' => '01923abc-0000-1234-1234-123456789abc',
                'name' => 'IT Group'
            ]);
        }

        if (UserModel::where('email', 'admin@admin.com')->count() === 0) {
            UserModel::factory()->create([
                'email' => 'admin@admin.com',
                'password' => bcrypt('password'),
                'org_unit_id' => '01923abc-1111-1234-1234-123456789abc',
                'uuid' => Str::uuid()->toString()
            ]);
        }
    }
}

