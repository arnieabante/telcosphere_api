<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUsSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'about_us_settings';

    protected $fillable = [
        'site_id',

        // Hero Section
        'about_us_title',
        'about_us_information',
        'about_us_image'
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