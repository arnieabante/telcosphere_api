<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pricing_settings';

    protected $fillable = [
        'site_id',
        'pricing_section_title',
        'pricing_section_text'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        static::creating(function ($pricingSettings) {
            if (empty($pricingSettings->site_id)) {
                $pricingSettings->site_id =
                    request()->header('site_id')
                    ?? auth()->user()->site_id
                    ?? 1;
            }

            if (auth()->check()) {
                $pricingSettings->created_by = auth()->id();
                $pricingSettings->updated_by = auth()->id();
            }
        });

        static::updating(function ($pricingSettings) {
            if (auth()->check()) {
                $pricingSettings->updated_by = auth()->id();
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