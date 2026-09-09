<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesoWifiHarvestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'PesoWifiHarvest',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'amountHarvested' => $this->amount_harvested,
                'harvestDate' => $this->created_at,
                'lessInternet' => $this->less_internet,
                'lessElectricity' => $this->less_electricity,
                'lessOthers' => $this->less_others,
                'totalDeductions' => $this->total_deductions,
                'resellersIncome' => $this->resellers_income,
                'ownerIncome' => $this->owner_income,
                'remarks' => $this->remarks,
                'collectedBy' => $this->collected_by,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('pesowifiharvests.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => [
                'pesoWifiClient' => new PesoWifiClientResource($this->whenLoaded('pesoWifiClient')),
                'collectedBy' => new UserResource($this->whenLoaded('collectedBy'))
            ],
            'links' => [
                'pesowifiharvest' => route('pesowifiharvests.show', $this->id)
            ]
        ];
    }
}
