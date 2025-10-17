<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'role',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('roles.show'), [
                        'siteId' => $this->site_id,
                        'userId' => $this->user_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => $this->whenLoaded('user', function() {
                return [
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => (string) $this->user->id,
                            'attributes' => [
                                'uuid' => $this->user->uuid,
                                'username' => $this->user->username,
                                'email' => $this->user->email,
                                'isActive' => $this->user->is_active
                            ]
                        ],
                        'links' => [
                            'user' => route('users.show', $this->user->id),
                            'related' => '' // TODO
                        ]
                    ],
                    /* TODO
                    'modules' => [
                        'data' => [
                            'type' => 'module',
                            'id' => (string) $this->module->id,
                            'attributes' => []
                        ]
                    ] */
                ];
            }),
            'links' => [
                'role' => route('roles.show', $this->id)
            ]
        ];
    }
}
