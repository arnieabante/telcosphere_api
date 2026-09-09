<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesoWifiAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'PesoWifiArea',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('pesowifiareas.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'pesowifiarea' => route('pesowifiareas.show', $this->id)
            ]
        ];
    }
}
