<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing>
 */
class BillingFactory extends Factory
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
            'client_id' => 1,
            'billing_date' => fake()->dateTimeThisYear(),
            'billing_remarks' => fake()->text(50),
            'billing_total' => fake()->randomFloat(2, 100, 10000),
            'billing_status' => fake()->randomElement(['paid', 'unpaid', 'pending']) ,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }
}
