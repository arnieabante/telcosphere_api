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
                    'role' => [
                        'data' => [
                            'type' => 'role',
                            'id' => (string) $this->role->id,
                            'attributes' => [
                                'uuid' => $this->role->uuid,
                                'name' => $this->role->name, 
                                'description' => $this->role->description,
                                'isActive' => $this->role->is_active
                            ]
                        ],
                        'links' => [
                            'role' => route('roles.show', $this->role->id),
                            'related' => '' // TODO
                        ]
                    ]
                ];
            }),
            // loads entire resource, can be optional. example url query: <url>?include=author
            // might not be needed for this resource
            /* 'includes' => [
                'company' => $this->whenLoaded('company', function () {
                    return new CompanyResource($this->user);
                })
            ], */
            'links' => [
                'user' => route('users.show', $this->id)
            ]
        ];
    }
}
