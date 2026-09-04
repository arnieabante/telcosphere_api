<?php

namespace App\Http\Requests\Api\FooterSettingsRequest;

class UpdateFooterSettingsRequest extends BaseFooterSettingsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'companyFooterTagline' => 'sometimes|required|string|max:255',
            'companyEmail' => 'sometimes|required|string|max:255',
            'companyTelephone' => 'sometimes|required|string|max:255',
            'companyCellphone' => 'sometimes|required|string|max:255',
            'companyAddress' => 'sometimes|required|string|max:255'
        ];
    }
}