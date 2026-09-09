<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FooterSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'footer_setting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'companyFooterTagline' => $this->company_footer_tagline,
                'companyEmail' => $this->company_email,
                'companyTelephone' => $this->company_telephone,
                'companyCellphone' => $this->company_cellphone,
                'companyAddress' => $this->company_address,
                $this->mergeWhen(
                    request()->routeIs('footersettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'footersetting' => route('footersettings.show', $this->id)
            ]
        ];
    }
}
