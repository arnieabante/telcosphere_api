<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'homepage_settings';

    protected $fillable = [
        'site_id',

        // Hero Section
        'hero_enabled',
        'hero_title',
        'hero_subtitle',

        // Primary Button
        'primary_button_text',
        'primary_button_url',

        // Images
        'background_image',
        'hero_image',

        // Display
        'text_alignment',
        'overlay_opacity',
    ];

    protected $casts = [
        'hero_enabled' => 'boolean',
        'overlay_opacity' => 'integer',
    ];

    protected $attributes = [
        'hero_enabled' => true,
        'text_alignment' => 'center',
        'overlay_opacity' => 40,
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        static::creating(function ($homepageSettings) {
            if (empty($homepageSettings->site_id)) {
                $homepageSettings->site_id =
                    request()->header('site_id')
                    ?? auth()->user()->site_id
                    ?? 1;
            }

            if (auth()->check()) {
                $homepageSettings->created_by = auth()->id();
                $homepageSettings->updated_by = auth()->id();
            }
        });

        static::updating(function ($homepageSettings) {
            if (auth()->check()) {
                $homepageSettings->updated_by = auth()->id();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}