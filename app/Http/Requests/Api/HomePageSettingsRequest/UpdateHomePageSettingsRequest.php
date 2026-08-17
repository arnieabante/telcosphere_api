<?php

namespace App\Http\Requests\Api\HomePageSettingsRequest;

class UpdateHomepageSettingsRequest extends BaseHomepageSettingsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteId' => 'sometimes|required|integer|exists:sites,id',

            // Hero Section
            'heroEnabled' => 'sometimes|required|boolean',
            'heroTitle' => 'nullable|string|max:255',
            'heroSubtitle' => 'nullable|string|max:255',

            // Primary Button
            'primaryButtonText' => 'nullable|string|max:255',
            'primaryButtonUrl' => 'nullable|string|max:255',

            // Images
            'backgroundImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'heroImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',

            // Display Options
            'textAlignment' => 'sometimes|required|in:left,center,right',
            'overlayOpacity' => 'sometimes|required|integer|min:0|max:100',
        ];
    }
}