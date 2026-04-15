<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class pesowifiarea extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'peso_wifi_areas';
    
    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'name',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a pesowifiarea
        static::creating(function ($pesowifiarea) {
            // but only when site_id is not already set
            if (empty($pesowifiarea->site_id)) {
                $pesowifiarea->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $pesowifiarea->created_by = auth()->id();
                $pesowifiarea->updated_by = auth()->id();
            }
        });

        static::updating(function ($pesowifiarea) {
            if (auth()->check()) {
                $pesowifiarea->updated_by = auth()->id();
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
