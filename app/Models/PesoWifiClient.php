<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class pesowificlient extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'peso_wifi_clients';
    
// default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'area_id',
        'name',
        'harvest_day',
        'reseller_share',
        'device_status',
        'last_harvest_date',
        'next_harvest_date',
        'is_harvested',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a pesowificlient
        static::creating(function ($pesowificlient) {
            // but only when site_id is not already set
            if (empty($pesowificlient->site_id)) {
                $pesowificlient->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $pesowificlient->created_by = auth()->id();
                $pesowificlient->updated_by = auth()->id();
            }
        });

        static::updating(function ($pesowificlient) {
            if (auth()->check()) {
                $pesowificlient->updated_by = auth()->id();
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

    public function pesoWifiArea()
    {
        return $this->belongsTo(\App\Models\pesoWifiArea::class, 'area_id');
    }
}
