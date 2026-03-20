<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class BillingCategory extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'date_cycle',
        'days_to_due_date',
        'days_to_disconnection_date',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a billingcategory
        static::creating(function ($billingcategory) {
            $billingcategory->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $billingcategory->created_by = auth()->id();
                $billingcategory->updated_by = auth()->id();
            }
        });

        static::updating(function ($billingcategory) {
            if (auth()->check()) {
                $billingcategory->updated_by = auth()->id();
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
