<?php

namespace App\Http\Requests\Api\InternetplanRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseInternetplanRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'name' => 'name',
            'monthly_subscription' => 'monthly_subscription',
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
