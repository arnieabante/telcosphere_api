<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internetplans>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;
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
            'expense_date' => '2025-12-12',
            'staff_name' => 'Elmar Malazarte',
            'total' => '1000.00',
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }
}
