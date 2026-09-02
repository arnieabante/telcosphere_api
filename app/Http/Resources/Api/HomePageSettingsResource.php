<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'homepagesetting',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'heroEnabled' => $this->hero_enabled,
                'heroTitle' => $this->hero_title,
                'heroSubtitle' => $this->hero_subtitle,
                'primaryButtonText' => $this->primary_button_text,
                'primaryButtonUrl' => $this->primary_button_url,
                'textAlignment' => $this->text_alignment,
                'overlayOpacity' => $this->overlay_opacity,
                'backgroundImage' => $this->background_image,
                $this->mergeWhen(
                    request()->routeIs('homepagesettings.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'homepagesetting' => route('homepagesettings.show', $this->id)
            ]
        ];
    }
}
