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
       'site_id' => 1,
       'is_active' => 1
    ];

    protected $fillable = [
        'name',
        'monthly_subscription',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a internetplan
        static::creating(function ($internetplan) {
            $internetplan->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
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
