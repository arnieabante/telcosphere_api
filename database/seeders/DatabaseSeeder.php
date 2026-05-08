<?php

namespace Database\Seeders;

use App\Models\Billing;
use App\Models\Item;
use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use App\Models\Role;
use App\Models\BillingCategory;
use App\Models\BillingItem;
use App\Models\Client;
use App\Models\ExpenseCategory;
use App\Models\Internetplan;
use App\Models\TicketCategory;
use App\Models\Site;
use App\Models\Server;

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
            ->count(19)
            ->create();

        // then Roles
        // triggers Users, assigns permissions
        Role::factory()
            ->count(1)
            ->hasUsers(5)
            ->create();

        Internetplan::factory()
            ->count(3)
            ->create();

        ExpenseCategory::factory()
            ->count(8)
            ->create();

        BillingCategory::factory()
            ->count(3)
            ->create();

        TicketCategory::factory()
            ->count(2)
            ->create();

        Server::factory()
            ->count(3)
            ->create();

        Site::factory()
            ->count(1)
            ->create();
    }
}
