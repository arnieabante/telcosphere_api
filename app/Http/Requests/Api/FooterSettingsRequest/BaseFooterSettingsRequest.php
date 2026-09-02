<?php

namespace App\Http\Requests\Api\FooterSettingsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseFooterSettingsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'siteId' => 'site_id',
            'companyFooterTagline' => 'company_footer_tagline',
            'companyEmail' => 'company_email',
            'companyTelephone' => 'company_telephone',
            'companyCellphone' => 'company_cellphone',
            'companyAddress' => 'company_address'
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
