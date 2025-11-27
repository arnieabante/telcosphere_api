<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $item = $this->getStaticSite();

        return [
            'uuid' => fake()->uuid(),
            'company_logo' => $item['company_logo'],   
            'company_banner' => $item['company_banner'],             
            'site_url' => $item['site_url'],             
            'company_name' => $item['company_name'], 
            'company_address' => $item['company_address'], 
            'company_email' => $item['company_email'], 
            'company_phone' => $item['company_phone'], 
            'company_telephone' => $item['company_telephone'],               
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
                'site_url' => "localhost:5173",
                'company_name' => 'TelcoSpere ERP Solutions',
                'company_address' => 'Cebu, City',
                'company_email' => 'app@telcosphere.co',
                'company_phone' => '',
                'company_telephone' => ''
            ],
            [
                'company_logo' => null,
                'company_banner' => null,
                'site_url' => "redjacobjaden.telcosphere.co",
                'company_name' => 'RedJacobJaden Internet Services',
                'company_address' => 'Cantinlo, Liptong, Santander, Cebu',
                'company_email' => 'redjacobjaden@telcosphere.co',
                'company_phone' => '',
                'company_telephone' => ''
            ]
        ];

        return array_shift($sites);
    }
}
