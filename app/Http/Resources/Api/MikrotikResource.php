<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MikrotikResource extends JsonResource
{
    /**
     * Transform the resource into an array. The router password never leaves the API.
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
                'serverId' => $this->server_id,
                'iPAddress' => $this->ip_address,
                'port' => $this->port,
                'useSsl' => $this->use_ssl,
                'username' => $this->username,
                'timeout' => $this->timeout,
                'lastSeenAt' => $this->last_seen_at,
                'lastCheckedAt' => $this->last_checked_at,
                'lastError' => $this->last_error,
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
