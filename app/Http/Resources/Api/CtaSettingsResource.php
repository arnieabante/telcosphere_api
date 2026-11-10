<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CtaSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'cta_setting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'ctaTitle' => $this->cta_title,
                'ctaText' => $this->cta_text,
                'ctaButton' => $this->cta_button,
                'ctaLabel' => $this->cta_label,
                $this->mergeWhen(
                    request()->routeIs('ctasettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'ctasetting' => route('ctasettings.show', $this->id)
            ]
        ];
    }
}
