<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesoWifiHarvest extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'peso_wifi_client_id',
        'amount_harvested',
        'less_internet',
        'less_electricity',
        'less_others',
        'total_deductions',
        'resellers_income',
        'owner_income',
        'remarks',
        'collected_by',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a pesowifiharvests
        static::creating(function ($pesowifiharvests) {
            // but only when site_id is not already set
            if (empty($pesowifiharvests->site_id)) {
                $pesowifiharvests->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $pesowifiharvests->created_by = auth()->id();
                $pesowifiharvests->updated_by = auth()->id();
            }
        });

        static::updating(function ($pesowifiharvests) {
            if (auth()->check()) {
                $pesowifiharvests->updated_by = auth()->id();
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

    public function pesoWifiClient()
    {
        return $this->belongsTo(\App\Models\PesoWifiClient::class, 'peso_wifi_client_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }
}
