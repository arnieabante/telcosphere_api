<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'module',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'isActive' => $this->is_active, 
                $this->mergeWhen(
                    request()->routeIs('modules.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            /* TODO: load through permissions
            'relationships' => $this->whenLoaded('roles', function() {
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
            */
            'links' => [
                'module' => route('modules.show', $this->id)
            ]
        ];
    }
}
