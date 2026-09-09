<?php

namespace App\Http\Requests\Api\PricingSettingsRequest;

class StorePricingSettingsRequest extends BasePricingSettingsRequest
{
    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'pricingSectionTitle' => 'sometimes|required|string|max:255',
            'pricingSectionText' => 'sometimes|required|string|max:255'
        ];
    }
}