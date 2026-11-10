<?php

namespace App\Http\Requests\Api\MikrotikRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseMikrotikRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'name' => 'name',
            'ipAddress' => 'ip_address',
            'port' => 'port',
            'useSsl' => 'use_ssl',
            'username' => 'username',
            'password' => 'password',
            'timeout' => 'timeout',
            'lastSeenAt' => 'last_seen_at',
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
