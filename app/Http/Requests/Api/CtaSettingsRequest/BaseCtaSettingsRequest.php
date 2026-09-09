<?php

namespace App\Http\Requests\Api\CtaSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseCtaSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'ctaTitle' => 'cta_title',
            'ctaText' => 'cta_text',
            'ctaButton' => 'cta_button',
            'ctaLabel' => 'cta_label'
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
