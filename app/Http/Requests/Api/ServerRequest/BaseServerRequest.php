<?php

namespace App\Http\Requests\Api\ServerRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseServerRequest extends FormRequest
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
