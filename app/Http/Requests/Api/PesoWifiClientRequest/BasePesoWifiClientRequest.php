<?php

namespace App\Http\Requests\Api\PesoWifiClientRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePesoWifiClientRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'areaId' => 'area_id',
            'name' => 'name',
            'harvestDay' => 'harvest_day',
            'resellerShare' => 'reseller_share',
            'deviceStatus' => 'device_status',
            'last_harvest_date' => 'lastHarvestDate',
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
