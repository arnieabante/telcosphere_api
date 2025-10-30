<?php

namespace App\Http\Resources\Api;

use App\Models\Permission;
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
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => $this->whenLoaded('modules', function () {
                return [
                    'modules' => ModuleResource::collection($this->modules)
                ];
            }),
            'links' => [
                'role' => route('roles.show', $this->uuid)
            ]
        ];
    }
}
