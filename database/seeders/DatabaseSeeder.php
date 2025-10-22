<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // seed Modules first
        Module::factory()
            ->count(4)
            ->create();

        // then Roles 
        // triggers Users, assigns permissions
        Role::factory()
            ->count(3)
            ->create();

        /*User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );*/
    }
}
