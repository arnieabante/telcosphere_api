<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'pricing_setting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'pricingSectionTitle' => $this->pricing_section_title,
                'pricingSectionText' => $this->pricing_section_text,
                $this->mergeWhen(
                    request()->routeIs('pricingsettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'pricingsetting' => route('pricingsettings.show', $this->id)
            ]
        ];
    }
}
