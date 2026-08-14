<?php

namespace App\Http\Requests\Api\HomePageSettingsRequest;

class ReplaceHomePageSettingsRequest extends BaseHomePageSettingsRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteId' => 'required|integer|exists:sites,id',

            // Hero Section
            'heroEnabled' => 'required|boolean',
            'heroTitle' => 'nullable|string|max:255',
            'heroSubtitle' => 'nullable|string|max:255',

            // Primary Button
            'primaryButtonText' => 'nullable|string|max:255',
            'primaryButtonUrl' => 'nullable|string|max:255',

            // Images
            'backgroundImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'heroImage' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',

            // Display
            'textAlignment' => 'required|in:left,center,right',
            'overlayOpacity' => 'required|integer|min:0|max:100',
        ];
    }
}