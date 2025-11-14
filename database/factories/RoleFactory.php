<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use PhpParser\Node\Expr\AssignOp\Mod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'name' => fake()->unique()->randomElement(['guest', 'manager', 'admin']),
            'description' => fake()->text(50),
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }

    public function configure(): static {
        return $this->afterCreating(function (Role $role) {

            if ($role->name == 'admin') {
                User::factory()->create([
                    'role_id' => $role->id,
                    'fullname' => 'Admin User',
                    'username' => 'admin',
                    'email' => 'admin@telcosphere.co'
                ]);

                $modules = Module::all();

                foreach ($modules as $module) {
                    Permission::factory()->create([
                        'role_id' => $role->id,
                        'module_id' => $module->id,
                        'is_read' => 1,
                        'is_write' => 1,
                        'is_delete' => 1,
                    ]);
                }
            } else {
                User::factory()->create([
                    'role_id' => $role->id
                ]);

                $modules = Module::inRandomOrder()->take(3)->get();

                foreach ($modules as $module) {
                    Permission::factory()->create([
                        'role_id' => $role->id,
                        'module_id' => $module->id
                    ]);
                }
            }
        });
    }
}
