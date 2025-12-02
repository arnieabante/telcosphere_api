<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'uuid'        => fake()->uuid(),
            'site_id'     => 1,
            'name'        => 'Admin',        // default, but you can override
            'description' => fake()->text(50),
            'is_active'   => 1,
            'created_by'  => 1,
            'updated_by'  => 1
        ];
    }

    /**
     * Create an Admin role for any site.
     */
    public function adminForSite($siteId): static
    {
        return $this->state(fn () => [
            'name'    => 'Admin',
            'site_id' => $siteId,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Role $role) {

            // Fetch modules ONLY for this role's site
            $modules = Module::where('site_id', $role->site_id)->get();

            // FULL ACCESS for Admin
            if ($role->name == 'Admin') {
                foreach ($modules as $module) {
                    Permission::factory()->create([
                        'role_id'   => $role->id,
                        'module_id' => $module->id,
                        'is_read'   => 1,
                        'is_write'  => 1,
                        'is_delete' => 1,
                    ]);
                }
                return;
            }

            // Non-admin: random 3 modules
            $randomModules = $modules->random(min(3, $modules->count()));
            foreach ($randomModules as $module) {
                Permission::factory()->create([
                    'role_id'   => $role->id,
                    'module_id' => $module->id,
                ]);
            }
        });
    }
}
