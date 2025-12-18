<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\BillingCategory;

use Illuminate\Database\Eloquent\Factories\Factory;
use PhpParser\Node\Expr\AssignOp\Mod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class BillingCategoryFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Administrative Cost', 'Billing and Collections', 'Employee Costs', 'Employee Welfare', 'Infrastructure Costs', 'Marketing and Sales', 'Operational Costs', 'Transportation Costs']),
            'description' => fake()->randomElement(['Office Supplies, Rent and Utilities, Legal and Professional Fees, Insurance', 'Billing Software, Payment Processing Fees, Collection Agency Fees', 'Salaries and Wages, Employee Benefits, Training and Development, Travel and Accommodation', 'Meals and Refreshments (e.g., Food for staff meetings or oncall technicians), Employee Engagement Activity', 'Network Equipment (routers, switches, servers), Fiber Optic Cables, Data Center Costs, Back-up Power System', 'Advertising and Promotions, Customer Acquisition Costs, Sales Commissions, Website Development and Maintenance', 'Internet Bandwidth, Software Licenses, Maintenance and Repairs, Technical Support', 'Fuel Expenses (e.g., gas for company vehicle or motorcycles), Vehicle Maintenance and Repairs, Travel Allowance for Field Technicians']),
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ];
    }
}
