<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationsMenuSeeder extends Seeder
{
    public function run(): void
    {
        $parentId = DB::table('modules')->insertGetId([
            'uuid' => (string) Str::uuid(), // ✅ ADD THIS
            'name' => 'Integrations',
            'icon' => 'fa fa-server',
            'parent_id' => 0,
            'description' => 'Integrations Menu',
            'url' => '',
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $items = [
            [
                'uuid' => (string) Str::uuid(), // ✅ ALSO REQUIRED
                'name' => 'Payment',
                'icon' => 'fa fa-credit-card',
                'parent_id' => $parentId,
                'description' => 'Payment',
                'url' => 'paymentsettings',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'SMS',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'SMS Settings',
                'url' => 'smssettings',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Mikrotik',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Wifi Collections',
                'url' => 'mikrotiksettings',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('modules')->insert($items);
    }
}