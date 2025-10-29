<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseUserRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'fullname' => 'fullname',
            'username' => 'username',
            'email' => 'email',
            'password' => 'password',
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
