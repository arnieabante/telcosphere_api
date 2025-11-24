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
                'parent_id' => $this->parent_id,
                'icon' => $this->icon,
                'url'  => $this->url,
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
                'permissions' => $this->whenPivotLoaded('permissions', function () {
                     return [
                        'isRead' => $this->pivot->is_read,
                        'isWrite' => $this->pivot->is_write,
                        'isDelete' => $this->pivot->is_delete
                     ];
                })
            ],
            'links' => [
                'module' => route('modules.show', $this->uuid)
            ]
        ];
    }
}
