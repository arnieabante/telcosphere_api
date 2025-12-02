<?php

namespace Database\Factories;

use App\Models\Internetplan;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internetplans>
 */
class InternetplanFactory extends Factory
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
            'icon' => "fa fa-edit",
            'name' => fake()->unique()->randomElement(['Plan 1000', 'Plan 1499', 'Plan 1699']),
            'monthly_subscription' => fake()->randomFloat('1000', '1499', '1699'),
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }
}
