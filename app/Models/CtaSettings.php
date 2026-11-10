<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtaSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cta_settings';

    protected $fillable = [
        'site_id',
        'cta_title',
        'cta_text',
        'cta_button',
        'cta_label'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        static::creating(function ($ctaSettings) {
            if (empty($ctaSettings->site_id)) {
                $ctaSettings->site_id =
                    request()->header('site_id')
                    ?? auth()->user()->site_id
                    ?? 1;
            }

            if (auth()->check()) {
                $ctaSettings->created_by = auth()->id();
                $ctaSettings->updated_by = auth()->id();
            }
        });

        static::updating(function ($ctaSettings) {
            if (auth()->check()) {
                $ctaSettings->updated_by = auth()->id();
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