<?php

namespace Database\Factories;

use App\Models\SiteBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteBankAccountFactory extends Factory
{
    protected $model = SiteBankAccount::class;

    public function definition(): array
    {
        $item = $this->getStaticSite();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'bank_name' => fake()->randomElement(['Gcash', 'PayMaya']),
            'account_name' => fake()->randomElement(['09565705461', '09974121504']),
            'account_number' => 'Elmar Malazarte',
            'account_qr' => null,
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
