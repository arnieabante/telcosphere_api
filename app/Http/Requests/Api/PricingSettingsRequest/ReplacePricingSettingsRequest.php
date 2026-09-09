<?php

namespace App\Http\Requests\Api\PricingSettingsRequest;

class ReplacePricingSettingsRequest extends BasePricingSettingsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'pricingSectionTitle' => 'sometimes|required|string|max:255',
            'pricingSectionText' => 'sometimes|required|string|max:255'
        ];
    }
}