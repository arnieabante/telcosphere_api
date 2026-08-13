<?php

namespace App\Http\Requests\Api\AboutUsSettingsRequest;

class StoreAboutUsSettingsRequest extends BaseAboutUsSettingsRequest
{
    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',
            'aboutUsTitle' => 'sometimes|required|string|max:255',
            'aboutUsInformation' => 'sometimes|required|string|max:255',
            'aboutUsImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096'
        ];
    }
}