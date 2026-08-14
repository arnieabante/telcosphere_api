<?php

namespace App\Http\Requests\Api\HomePageSettingsRequest;

class StoreHomePageSettingsRequest extends BaseHomePageSettingsRequest
{
    public function rules(): array
    {
        return [
            'siteId' => 'integer|exists:sites,id',

            // Hero Section
            'heroEnabled' => 'required|boolean',
            'heroTitle' => 'nullable|string|max:255',
            'heroSubtitle' => 'nullable|string|max:255',

            // Buttons
            'primaryButtonText' => 'nullable|string|max:255',
            'primaryButtonUrl' => 'nullable|string|max:255',

            // Images
            'backgroundImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'heroImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',

            // Display
            'textAlignment' => 'nullable|in:left,center,right',
            'overlayOpacity' => 'nullable|integer|min:0|max:100',
        ];
    }
}