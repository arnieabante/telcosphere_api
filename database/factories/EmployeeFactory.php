<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'site_id' => 1,
            'firstname' => 'Elmar',
            'middlename' => 'Frasco',
            'lastname' => 'Malazarte',
            'birth_date' => '2025-01-01',
            'gender' => fake()->randomElement('Male', 'Female', 'Binary'),
            'civil_status' => fake()->randomElement('Single', 'Married', 'Widowed', 'Divorced'),
            'email_address' => 'elmar.malazarte@gmail.com',
            'contact_no' => '09954123241',
            'home_address' => 'Pit-os, Cebu City',
            'emergency_contact_no' => '09456738876',
            'employee_id' => '25-00001',
            'date_hired' => '2025-01-01',
            'department' => fake()->randomElement('Accounting', 'Sales', 'Techinical'),
            'designation' => fake()->randomElement('Accounting Staff', 'Sales Staff', 'Technical Staff'),
            'work_location' => fake()->randomElement('Samboan', 'Santander', 'Oslob'),
            'access_level' => fake()->randomElement('Staff', 'Technical', 'Admin'),
            'user_id' => 1,
            'shift_schedule_from' => '8:00 AM',
            'shift_schedule_to' => '5:00 PM',
            'salary_rate_per_day' => '500.00',
            'hourly_rate_per_day' => '62.50',
            'payment_method' => 'Bank',
            'bank_name' => 'PNB',
            'bank_account_no' => '1234-1234-4567-7890',
            'sss_no' => '111-111-111',
            'pagibig_no' => '222-22-222-22',
            'philhealth_no' => '123-123-123',
            'tin' => 'TIN-000001',
            'employee_type' => fake()->randomElement('Casual', 'Regular', 'Contractor'),
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
