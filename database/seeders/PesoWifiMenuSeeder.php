<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PesoWifiMenuSeeder extends Seeder
{
    public function run(): void
    {
        $parentId = DB::table('modules')->insertGetId([
            'uuid' => (string) Str::uuid(), // ✅ ADD THIS
            'name' => 'Peso Wifi Sections',
            'icon' => 'fa fa-globe',
            'parent_id' => 0,
            'description' => 'Peso Wifi Sections',
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
                'name' => 'Peso Wifi Dashboard',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Wifi Dashboard',
                'url' => 'pesowifidashboard',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Peso Wifi Clients',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Wifi Clients',
                'url' => 'pesowificlients',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Peso Wifi Collections',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Wifi Collections',
                'url' => 'pesowificollections',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'For Harvest',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'For Harvest',
                'url' => 'pesowififorharvest',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Peso Wifi Collection Report',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Monthly collection report for peso wifi clients.',
                'url' => 'pesowifimonthlycollectionreports',
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Peso Wifi Areas',
                'icon' => 'fa fa-globe',
                'parent_id' => $parentId,
                'description' => 'Peso Wifi Areas',
                'url' => 'pesowifiareas',
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