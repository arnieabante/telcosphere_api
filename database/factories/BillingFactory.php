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
        $fakeTotal = fake()->randomFloat(2, 100, 10000);

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'client_id' => 1,
            'invoice_number' => 'INV-' . fake()->randomNumber(6, true),
            'billing_type' => fake()->numberBetween(1, 3),
            'billing_date' => fake()->dateTimeThisYear(),
            'billing_description' => fake()->text(50),
            'billing_remarks' => fake()->text(50),
            'billing_total' => $fakeTotal,
            'billing_offset' => '0.00',
            'billing_balance' => $fakeTotal,
            'billing_status' => 'Paid', // fake()->randomElement(['Pending', 'Billed', 'Paid']),
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
            $itemCount = 2;
            $billingItemAmount = round(($billing->billing_total / $itemCount), 2);

            BillingItem::factory()
                ->count($itemCount)
                ->create([
                    'billing_id' => $billing->id,
                    'billing_item_amount' => $billingItemAmount,
                    'billing_item_balance' => $billingItemAmount
                ]);
        });
    }
}
