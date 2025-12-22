<?php

namespace Database\Factories;

use App\Models\Client;
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
            'first_name' => 'Elmar',
            'middle_name' => 'Frasco',
            'last_name' => 'Malazarte',
            'mobile_no' => '09454567891',
            'email' => 'elmar_malazarte@gmail.com',
            'house_no' => 'Near Basketball Court, Liloan, Santander, Cebu',
            'latitude' => '9.4171',
            'longitude' => '123.3352',
            'account_no' => 'ACCT-0144555778888',
            'installation_date' => $this->faker->date(),
            'installation_fee' => '1000.00',
            'balance_from_prev_billing' => '0.00',
            'prorate_fee' => '0.00',
            'prorate_start_date' => null, 
            'prorate_end_date' => null,
            'last_auto_billing_date' => '2025-12-15',
            'inactive_date' => $this->faker->optional()->date(),
            'notes' => 'Seeded from our database',
            'facebook_profile_url' => 'https://www.messenger.com/e2ee/t/7850110205011996',
            'billing_category_id' => 1,
            'server_id' => 1,
            'internet_plan_id' => 1,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
