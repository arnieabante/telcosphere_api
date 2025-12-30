<?php

namespace Database\Factories;

use App\Models\ExpenseItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Api\BillingItems>
 */
class ExpenseItemFactory extends Factory
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
            'expense_id' => NULL,
            'expense_category' => 1,
            'expense_remark' => fake()->text(50),
            'amount' => '1000.00',
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
}
