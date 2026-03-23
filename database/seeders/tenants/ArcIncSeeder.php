<?php

namespace Database\Seeders\Tenants;

use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\Module;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ArcIncSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create site (or find existing)
        $site = Site::firstOrCreate([
            'company_name' => 'Arc Ventures, Inc.',
            'company_address' => 'Quezon, City',
        ], [
            'uuid' => fake()->uuid(),
            'site_url' => 'arc-inc.telcosphere.co',
            'company_email' => 'admin.arc-inc@telcosphere.co',
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        // 2. Create Administrator role
        $adminRole = Role::firstOrCreate([
            'site_id' => $site->id,
            'name' => 'Admin',
            'description'=> 'Administrator'
        ], [
            'uuid' => fake()->uuid(),
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);

        // 3. Assign FULL permissions to Administrator
        
        $modules = Module::where('is_active', true)->get();
        foreach ($modules as $module) {
            Permission::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'role_id' => $adminRole->id,
                    'module_id' => $module->id
                ],
                [
                    'uuid' => fake()->uuid(),
                    'is_read' => 1,
                    'is_write' => 1,
                    'is_delete' => 1,
                    'is_active' => 1,
                    'created_by' => 1,
                    'updated_by' => 1
                ]
            );
        }

        // 5. Create default admin user
        User::firstOrCreate([
            'username' => 'arc-inc.admin',
            'site_id' => $site->id
        ], [
            'uuid' => fake()->uuid(),
            'fullname' => 'Administrator',
            'email' => 'arc-inc.admin@telcosphere.com',
            'password' => Hash::make('A3cInc@password'),
            'role_id' => $adminRole->id,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
    }
}