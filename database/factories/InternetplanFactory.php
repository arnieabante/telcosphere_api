<?php

namespace Database\Factories;

use App\Models\Internetplan;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internetplans>
 */
class InternetplanFactory extends Factory
{
    protected $model = Internetplan::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = $this->getStaticModule();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'icon' => "fa fa-edit",
            'name' => $item['name'],
            'monthly_subscription' => $item['monthly_subscription'],
            'is_active' => 1,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }

    /**
     * Pull the next static module record.
     */
    private function getStaticModule()
    {
        static $plans = [
            ['name' => 'Plan 1000', 'monthly_subscription' => '1000.00'],
            ['name' => 'Plan 1499', 'monthly_subscription' => '1499.00'],
            ['name' => 'Plan 1699', 'monthly_subscription' => '1699.00'],
        ];

        return array_shift($plans);
    }
}
