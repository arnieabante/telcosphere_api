<?php

namespace App\Http\Requests\Api\SmsSettingsRequest;

use App\Models\SmsSettings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BaseSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'apiKey' => 'sometimes|required|string|max:255',
            'senderName' => 'sometimes|nullable|string|max:50',
            'apiUrl' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!SmsSettings::isAllowedUrl($value)) {
                        $fail('The API URL must be an https://api.semaphore.co address.');
                    }
                },
            ],
            'isEnabled' => 'sometimes|required|boolean',
            'testMode' => 'sometimes|required|boolean',
            'connectionTimeout' => 'sometimes|required|integer|min:1|max:' . config('sms.max_connection_timeout'),
            'retryAttempts' => 'sometimes|required|integer|min:0|max:' . config('sms.max_retry_attempts'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->boolean('isEnabled') || $this->filled('apiKey') || $this->boolean('testMode')) {
                    return;
                }

                // SiteScope limits this to the logged-in user's own settings
                if (blank(SmsSettings::first()?->api_key)) {
                    $validator->errors()->add('apiKey', 'An API key is required to enable SMS.');
                }
            },
        ];
    }

    /**
     * site_id is deliberately not mappable: a site is always taken from the logged-in user.
     */
    public function mappedAttributes(): array {
        $attributes = [
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
