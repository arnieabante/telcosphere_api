<?php

namespace App\Http\Requests\Api\ModuleRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseModuleRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'name' => 'name',
            'description' => 'description',
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
