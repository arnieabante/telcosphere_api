<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'sms_setting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                // the key itself never leaves the server
                'hasApiKey' => filled($this->api_key),
                'senderName' => $this->sender_name,
                'apiUrl' => $this->api_url,
                'isEnabled' => $this->is_enabled,
                'testMode' => $this->test_mode,
                'connectionTimeout' => $this->connection_timeout,
                'retryAttempts' => $this->retry_attempts,
                $this->mergeWhen(
                    request()->routeIs('smssettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'smssetting' => route('smssettings.show', $this->uuid)
            ]
        ];
    }
}