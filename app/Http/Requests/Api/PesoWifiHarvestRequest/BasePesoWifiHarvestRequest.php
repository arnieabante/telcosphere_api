<?php

namespace App\Http\Requests\Api\PesoWifiHarvestRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePesoWifiHarvestRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'pesoWifiClientId' => 'peso_wifi_client_id',
            'amountHarvested' => 'amount_harvested',
            'lessInternet' => 'less_internet',
            'lessElectricity' => 'less_electricity',
            'lessOthers' => 'less_others',
            'totalDeductions' => 'total_deductions',
            'resellersIncome' => 'resellers_income',
            'ownerIncome' => 'owner_income',
            'remarks' => 'remarks',
            'collectedBy' => 'collected_by',
            'isActive' => 'is_active'
        ];

        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
