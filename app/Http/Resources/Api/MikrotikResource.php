<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MikrotikResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Mikrotik',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'iPAddress' => $this->ip_address,
                'port' => $this->port,
                'useSsl' => $this->use_ssl,
                'username' => $this->username,
                'password' => $this->password,
                'timeout' => $this->timeout,
                'lastSeenAt' => $this->last_seen_at,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('mikrotiks.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'mikrotiks' => route('mikrotiks.show', $this->id)
            ]
        ];
    }
}
