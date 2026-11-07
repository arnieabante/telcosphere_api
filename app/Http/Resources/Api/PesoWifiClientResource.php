<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesoWifiClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'PesoWifiClient',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'areaId' => $this->area_id,
                'name' => $this->name,
                'harvestDay' => $this->harvest_day,
                'resellerShare' => $this->reseller_share,
                'deviceStatus' => $this->device_status,
                'lastHarvestDate' => $this->last_harvest_date,
                'nextHarvestDate' => $this->next_harvest_date,
                'isHarvested' => $this->is_harvested,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('pesowificlients.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => [
                'pesoWifiArea' => new PesoWifiAreaResource($this->whenLoaded('pesoWifiArea'))
            ],
            'links' => [
                'pesowificlient' => route('pesowificlients.show', $this->id)
            ]
        ];
    }
}
