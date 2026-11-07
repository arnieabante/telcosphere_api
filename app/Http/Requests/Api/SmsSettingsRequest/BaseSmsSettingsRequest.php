<?php

namespace App\Http\Requests\Api\SmsSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseSmsSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'apiKey' => 'api_key',
            'senderName' => 'sender_name',
            'apiUrl' => 'api_url',
            'isEnabled' => 'is_enabled',
            'testMode' => 'test_mode',
            'connectionTimeout' => 'connection_timeout',
            'retryAttempts' => 'retry_attempts'
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
