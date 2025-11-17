<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
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
            'role_id' => Role::factory(),
            'module_id' => Module::factory(),
            'is_read' => rand(0, 1),
            'is_write' => rand(0, 1),
            'is_delete' => rand(0, 1),
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }
}
