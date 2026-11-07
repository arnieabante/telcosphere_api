<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'aboutus_setting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'aboutUsTitle' => $this->about_us_title,
                'aboutUsInformation' => $this->about_us_information,
                'aboutUsImage' => $this->about_us_image,
                $this->mergeWhen(
                    request()->routeIs('aboutussettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'aboutussetting' => route('aboutussettings.show', $this->id)
            ]
        ];
    }
}
