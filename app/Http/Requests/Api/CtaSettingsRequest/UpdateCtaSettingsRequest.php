<?php

namespace App\Http\Requests\Api\CtaSettingsRequest;

class UpdateCtaSettingsRequest extends BaseCtaSettingsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'ctaTitle' => 'sometimes|required|string|max:255',
            'ctaText' => 'sometimes|required|string|max:255',
            'ctaButton' => 'sometimes|required|string|max:255',
            'ctaLabel' => 'sometimes|required|string|max:255'
        ];
    }
}