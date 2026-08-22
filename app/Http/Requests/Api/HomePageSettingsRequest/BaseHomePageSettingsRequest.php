<?php

namespace App\Http\Requests\Api\HomepageSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseHomepageSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'heroEnabled' => 'hero_enabled',
            'heroTitle' => 'hero_title',
            'heroSubtitle' => 'hero_subtitle',
            'primaryButtonText' => 'primary_button_text',
            'primaryButtonUrl' => 'primary_button_url',
            'backgroundImage' => 'background_image',
            'heroImage' => 'hero_image',
            'textAlignment' => 'text_alignment',
            'overlayOpacity' => 'overlay_opacity',
            'isActive' => 'is_active'
        ];

        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
