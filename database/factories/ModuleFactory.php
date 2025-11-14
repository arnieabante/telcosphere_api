<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
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
            'parent_id' => 1,
            'icon' => "fa fa-edit",
            'name' => fake()->unique()->randomElement([
                'Internet Plans',
                'Servers', 
                'SR Categories',
                'Billing Categories',
                'Employees',
                'Roles',
                'Users'
            ]),
            'description' => fake()->text(50),
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }
}
