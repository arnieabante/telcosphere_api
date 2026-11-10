<?php

namespace App\Http\Requests\Api\PricingSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePricingSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'pricingSectionTitle' => 'pricing_section_title',
            'pricingSectionText' => 'pricing_section_text'
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
