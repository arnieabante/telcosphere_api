<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\TicketCategory;

use Illuminate\Database\Eloquent\Factories\Factory;
use PhpParser\Node\Expr\AssignOp\Mod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class TicketCategoryFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Installation', 'Repairs']),
            'description' => null,
            'is_active' => 1,
            'created_by' => 1, 
            'updated_by' => 1 
        ];
    }
}
