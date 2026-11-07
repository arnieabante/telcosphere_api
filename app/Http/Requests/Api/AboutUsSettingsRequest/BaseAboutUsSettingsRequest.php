<?php

namespace App\Http\Requests\Api\AboutUsSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseAboutUsSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'aboutUsTitle' => 'about_us_title',
            'aboutUsInformation' => 'about_us_information',
            'aboutUsImage' => 'about_us_image'
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
