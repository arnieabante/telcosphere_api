<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;

use Illuminate\Database\Eloquent\Factories\Factory;
use PhpParser\Node\Expr\AssignOp\Mod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $expense = $this->getStaticExpenseCategories();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'name' => $expense['name'],
            'description' => $expense['description'],
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }

    /**
     * Pull the next static expense categories record
     */
    private function getStaticExpenseCategories()
    {
        static $expenses = [
            ['name' => 'Administrative Cost', 'description' => 'Office Supplies, Rent and Utilities, Legal and Professional Fees, Insurance'],
            ['name' => 'Billing and Collections', 'description' => 'Billing Software, Payment Processing Fees, Collection Agency Fees'],
            ['name' => 'Employee Costs', 'description' => 'Salaries and Wages, Employee Benefits, Training and Development, Travel and Accommodation'],
            ['name' => 'Employee Welfare', 'description' => 'Meals and Refreshments (e.g., Food for staff meetings or oncall technicians), Employee Engagement Activity'],
            ['name' => 'Infrastructure Costs', 'description' => 'Network Equipment (routers, switches, servers), Fiber Optic Cables, Data Center Costs, Back-up Power System'],
            ['name' => 'Marketing and Sales', 'description' => 'Advertising and Promotions, Customer Acquisition Costs, Sales Commissions, Website Development and Maintenance'],
            ['name' => 'Operational Costs', 'description' => 'Internet Bandwidth, Software Licenses, Maintenance and Repairs, Technical Support'],
            ['name' => 'Transportation Costs', 'description' => 'Fuel Expenses (e.g., gas for company vehicle or motorcycles), Vehicle Maintenance and Repairs, Travel Allowance for Field Technicians'],

        ];

        $expense = array_shift($expenses);

        return $expense;
    }
}
