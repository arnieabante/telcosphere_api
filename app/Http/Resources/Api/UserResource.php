<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'fullname' => $this->fullname,
                'username' => $this->username,
                'email' => $this->email,
                'roleName' => $this->role?->name,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('users.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => $this->whenLoaded('role', function() {
                return [
                    'role' => new RoleResource($this->role)
                ];
            }),
            'links' => [
                'user' => route('users.show', $this->uuid)
            ]
        ];
    }
}
