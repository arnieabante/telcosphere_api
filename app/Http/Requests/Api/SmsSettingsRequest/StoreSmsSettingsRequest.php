<?php

namespace App\Http\Requests\Api\SmsSettingsRequest;

class StoreSmsSettingsRequest extends BaseSmsSettingsRequest
{
    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'apiKey' => 'sometimes|required|string|max:255',
            'senderName' => 'sometimes|required|string|max:50',
            'apiUrl' => 'sometimes|required|string|max:255',
            'isEnabled' => 'sometimes|required|boolean',
            'testMode' => 'sometimes|required|boolean',
            'connectionTimeout' => 'sometimes|required|integer|min:1',
            'retryAttempts' => 'sometimes|required|integer|min:0'
        ];
    }
}