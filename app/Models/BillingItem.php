<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    /** @use HasFactory<\Database\Factories\Api\BillingItemsFactory> */
    use HasFactory, HasUlids;

    // default values
    protected $attributes = [
        'site_id' => 1,
        'is_active' => 1
    ];

    protected $fillable = [
        'billing_id',
        'billing_item_name',
        'billing_item_particulars',
        'billing_item_quantity',
        'billing_item_price',
        'billing_item_amount',
        'billing_item_offset',
        'billing_item_balance',
        'billing_item_remark',
        'billing_status'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a billing item
        static::creating(function ($billingItem) {
            $billingItem->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
             if (auth()->check()) {
                $billingItem->created_by = auth()->id();
                $billingItem->updated_by = auth()->id();
            }
        });

         static::updating(function ($billingItem) {
            if (auth()->check()) {
                $billingItem->updated_by = auth()->id();
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

    public function billing(): BelongsTo {
        return $this->belongsTo(Billing::class);
    }
}
