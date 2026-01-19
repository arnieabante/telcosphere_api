<?php

namespace Database\Factories;

use App\Models\BillingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Api\BillingItems>
 */
class BillingItemFactory extends Factory
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
            'billing_item_name' => fake()->words(2, true),
            'billing_item_particulars' => fake()->words(2, true),
            'billing_item_quantity' => fake()->numberBetween(1, 20),
            'billing_item_price' => '0.00',
            'billing_item_amount' => NULL,
            'billing_item_offset' => '0.00',
            'billing_item_balance' => NULL,
            'billing_item_remark' => fake()->text(50),
            'billing_status' => 'Paid', // fake()->randomElement(['Pending', 'Billed', 'Paid']),
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }

    public function configure(): static {
        // set item price base on item_total and quantity
        return $this->afterCreating(function (BillingItem $item) {
            $item->update([
                'billing_item_price' => round(
                    ($item->billing_item_amount / $item->billing_item_quantity), 
                    2
                )
            ]);
        });
    }
}
