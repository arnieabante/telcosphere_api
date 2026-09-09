<?php

namespace App\Http\Requests\Api\PesoWifiAreaRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePesoWifiAreaRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'name' => 'name',
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
