<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;

class Internetplan extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'internetplans';

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'name',
        'monthly_subscription',
        'is_featured',
        'features',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'features' => 'array',
        'monthly_subscription' => 'decimal:2',
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a internetplan
        static::creating(function ($internetplan) {
            // but only when site_id is not already set
            if (empty($internetplan->site_id)) {
                $internetplan->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $internetplan->created_by = auth()->id();
                $internetplan->updated_by = auth()->id();
            }
        });

        static::updating(function ($internetplan) {
            if (auth()->check()) {
                $internetplan->updated_by = auth()->id();
            }
        });
    }

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }
}
