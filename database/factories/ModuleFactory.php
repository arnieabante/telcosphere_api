<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        $item = $this->getStaticModule();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'parent_id' => $item['parent_id'],   // static
            'icon' => $item['icon'],             // static
            'name' => $item['name'],             // static
            'description' => $item['description'], // static
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Pull the next static module record.
     */
    private function getStaticModule()
    {
        static $modules = [
            [
                'name' => 'Dashboard',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24" viewBox="0 0 24 24"><path d="M3 13h1v7c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2v-7h1a1 1 0 0 0 .707-1.707l-9-9a.999.999 0 0 0-1.414 0l-9 9A1 1 0 0 0 3 13zm7 7v-5h4v5h-4zm2-15.586 6 6V15l.001 5H16v-5c0-1.103-.897-2-2-2h-4c-1.103 0-2 .897-2 2v5H6v-9.586l6-6z" /></svg>',
                'parent_id' => 0,
                'description' => 'Main overview of the system'
            ],
            [
                'name' => 'Clients',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24"  viewBox="0 0 24 24"><path d="M13.07 10.41A5 5 0 0 0 13.07 4.59A3.39 3.39 0 0 1 15 4A3.5 3.5 0 0 1 15 11A3.39 3.39 0 0 1 13.07 10.41M5.5 7.5A3.5 3.5 0 1 1 9 11A3.5 3.5 0 0 1 5.5 7.5M7.5 7.5A1.5 1.5 0 1 0 9 6A1.5 1.5 0 0 0 7.5 7.5M16 17V19H2V17S2 13 9 13 16 17 16 17M14 17C13.86 16.22 12.67 15 9 15S4.07 16.31 4 17M15.95 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13Z" /></svg>',
                'parent_id' => 0,
                'description' => 'Manage clients'
            ],
            [
                'name' => 'Billing',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24"  viewBox="0 0 24 24"><path d="M15 16.69V13H16.5V15.82L18.94 17.23L18.19 18.53L15 16.69M10.87 20.76L9 22L6 20L3 22V3H21V11.1C22.24 12.36 23 14.09 23 16C23 19.87 19.87 23 16 23C13.97 23 12.14 22.14 10.87 20.76M9.73 19.11C9.26 18.17 9 17.12 9 16C9 12.13 12.13 9 16 9C17.07 9 18.09 9.24 19 9.67V5H5V18.26L6 17.6L9 19.6L9.73 19.11M16 21C18.76 21 21 18.76 21 16C21 13.24 18.76 11 16 11C13.24 11 11 13.24 11 16C11 18.76 13.24 21 16 21Z" /></svg>',
                'parent_id' => 0,
                'description' => 'Manage billing'
            ],
            [
                'name' => 'Collections',
                'icon' => 'fa fa-ticket',
                'parent_id' => 0,
                'description' => 'Manage collection and payments'
            ],
            [
                'name' => 'Service Request',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24"  viewBox="0 0 24 24"><path d="M20,12A2,2 0 0,0 22,14V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V14C3.11,14 4,13.1 4,12A2,2 0 0,0 2,10V6C2,4.89 2.9,4 4,4H20A2,2 0 0,1 22,6V10A2,2 0 0,0 20,12M16.5,16.25C16.5,14.75 13.5,14 12,14C10.5,14 7.5,14.75 7.5,16.25V17H16.5V16.25M12,12.25A2.25,2.25 0 0,0 14.25,10A2.25,2.25 0 0,0 12,7.75A2.25,2.25 0 0,0 9.75,10A2.25,2.25 0 0,0 12,12.25Z" /></svg>',
                'parent_id' => 0,
                'description' => 'Manage support tickets'
            ],
            [
                'name' => 'Maintenance',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24" viewBox="0 0 24 24"><path d="M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10M10,22C9.75,22 9.54,21.82 9.5,21.58L9.13,18.93C8.5,18.68 7.96,18.34 7.44,17.94L4.95,18.95C4.73,19.03 4.46,18.95 4.34,18.73L2.34,15.27C2.21,15.05 2.27,14.78 2.46,14.63L4.57,12.97L4.5,12L4.57,11L2.46,9.37C2.27,9.22 2.21,8.95 2.34,8.73L4.34,5.27C4.46,5.05 4.73,4.96 4.95,5.05L7.44,6.05C7.96,5.66 8.5,5.32 9.13,5.07L9.5,2.42C9.54,2.18 9.75,2 10,2H14C14.25,2 14.46,2.18 14.5,2.42L14.87,5.07C15.5,5.32 16.04,5.66 16.56,6.05L19.05,5.05C19.27,4.96 19.54,5.05 19.66,5.27L21.66,8.73C21.79,8.95 21.73,9.22 21.54,9.37L19.43,11L19.5,12L19.43,13L21.54,14.63C21.73,14.78 21.79,15.05 21.66,15.27L19.66,18.73C19.54,18.95 19.27,19.04 19.05,18.95L16.56,17.95C16.04,18.34 15.5,18.68 14.87,18.93L14.5,21.58C14.46,21.82 14.25,22 14,22H10M11.25,4L10.88,6.61C9.68,6.86 8.62,7.5 7.85,8.39L5.44,7.35L4.69,8.65L6.8,10.2C6.4,11.37 6.4,12.64 6.8,13.8L4.68,15.36L5.43,16.66L7.86,15.62C8.63,16.5 9.68,17.14 10.87,17.38L11.24,20H12.76L13.13,17.39C14.32,17.14 15.37,16.5 16.14,15.62L18.57,16.66L19.32,15.36L17.2,13.81C17.6,12.64 17.6,11.37 17.2,10.2L19.31,8.65L18.56,7.35L16.15,8.39C15.38,7.5 14.32,6.86 13.12,6.62L12.75,4H11.25Z" /></svg>',
                'parent_id' => 0,
                'description' => 'Client and customer records'
            ],
            [
                'name' => 'Internet Plans',
                'icon' => 'fa fa-globe',
                'parent_id' => 6,
                'description' => 'Internet bandwidth plans'
            ],
            [
                'name' => 'Servers',
                'icon' => 'fa fa-server',
                'parent_id' => 6,
                'description' => 'List of hosted servers'
            ],
            [
                'name' => 'SR Categories',
                'icon' => 'fa fa-server',
                'parent_id' => 6,
                'description' => 'List of SR Categories'
            ],
            [
                'name' => 'Billing Categories',
                'icon' => 'fa fa-server',
                'parent_id' => 6,
                'description' => 'List of billing categories'
            ],
            [
                'name' => 'Employees',
                'icon' => 'fa fa-id-badge',
                'parent_id' => 6,
                'description' => 'Employee management'
            ],
            [
                'name' => 'Roles',
                'icon' => 'fa fa-lock',
                'parent_id' => 6,
                'description' => 'Role-based access control'
            ],
            [
                'name' => 'Users',
                'icon' => 'fa fa-user',
                'parent_id' => 6,
                'description' => 'System user accounts'
            ],
            [
                'name' => 'Reports',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" className="side-menu__icon" width="24" height="24" viewBox="0 0 24 24"><path d="M15 3H5C3.89 3 3 3.89 3 5V10.82C5.55 8.37 9.59 8.4 12.1 10.9C14.63 13.44 14.63 17.56 12.1 20.1C11.74 20.45 11.35 20.74 10.94 21H19C20.11 21 21 20.11 21 19V9L15 3M14 10V4.5L19.5 10H14M7.5 11C5 11 3 13 3 15.5C3 16.38 3.25 17.21 3.69 17.9L.61 21L2 22.39L5.12 19.32C5.81 19.75 6.63 20 7.5 20C10 20 12 18 12 15.5S10 11 7.5 11M7.5 18C6.12 18 5 16.88 5 15.5S6.12 13 7.5 13 10 14.12 10 15.5 8.88 18 7.5 18Z" /></svg>',
                'parent_id' => 0,
                'description' => 'System generated reports'
            ],
            [
                'name' => 'Statement of Account',
                'icon' => 'fa fa-key',
                'parent_id' => 14,
                'description' => 'Generation of Statemement of Account'
            ],
        ];

        return array_shift($modules);
    }
}
