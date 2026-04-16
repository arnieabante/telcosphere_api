<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteBankAccountFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $item = $this->getStaticSite();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => $item['site_id'],
            'bank_name' => $item['bank_name'],
            'account_name' => $item['account_name'],
            'account_number' => $item['account_number'],
            'account_qr' => $item['account_qr '],
            'is_active' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Pull the next static site record.
     */
    private function getStaticSite()
    {
        static $sites = [
            [
                'company_logo' => null,
                'company_banner' => null,
                'site_url' => "app.telcosphere.co",
                'company_name' => 'TelcoSpere ERP Solutions',
                'company_address' => 'Cebu, City',
                'company_email' => 'abantesoft@telcosphere.co',
                'company_phone' => '',
                'company_telephone' => ''
            ]
        ];

        return array_shift($sites);
    }
}
