<?php

namespace Database\Factories;

use App\Models\Billing;
use App\Models\BillingItem;
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
            'billing_status' => fake()->randomElement(['Pending', 'Billed', 'Paid']),
            'billing_cutoff' => fake()->dateTimeThisYear(),
            'disconnection_date' => fake()->dateTimeThisYear(),
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }

    public function configure(): static {
        // create fix billing items with equal amounts to match billing total
        return $this->afterCreating(function (Billing $billing) {
            $itemCount = 3;
            $billingItemAmount = round(($billing->billing_total / $itemCount), 2);

            BillingItem::factory()
                ->count($itemCount)
                ->create([
                    'billing_id' => $billing->id,
                    'billing_item_amount' => $billingItemAmount
                ]);
        });
    }
}
