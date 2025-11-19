<?php

namespace Database\Factories\Api;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Api\BillingItems>
 */
class BillingItemsFactory extends Factory
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
            'billing_id' => NULL,
            'billing_item_name' => fake()->text(50),
            'billing_item_quantity' => fake()->randomNumber(2, false),
            'billing_item_remark' => fake()->text(50),
            'billing_item_amount' => fake()->randomFloat(2, 100, 10000),
            'billing_item_total' => fake()->randomFloat(2, 100, 10000),
            'billing_status' => fake()->randomElement(['paid', 'unpaid', 'pending']) ,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }
}
