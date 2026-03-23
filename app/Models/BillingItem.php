<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BillingItem extends Model
{
    /** @use HasFactory<\Database\Factories\Api\BillingItemsFactory> */
    use HasFactory, HasUlids;

    // default values
    protected $attributes = [
        'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
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

        // Auto-assign site_id when creating a billing
        static::creating(function ($billingItem) {
            // but only when site_id is not already set
            if (empty($billingItem->site_id)) {
                $billingItem->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            $userId = auth()->id();
            if (!$userId) {
                $userId = User::where('site_id', $billingItem->site_id)
                    ->whereHas('role', function ($q) {
                        $q->where('description', 'Administrator');
                    })
                    ->value('id');
            }

            $billingItem->created_by = $userId;
            $billingItem->updated_by = $userId;
        });

        static::updating(function ($billingItem) {

            $userId = auth()->id();
            if (!$userId) {
                $userId = User::where('site_id', $billingItem->site_id)
                    ->whereHas('role', function ($q) {
                        $q->where('description', 'Administrator');
                    })
                    ->value('id');
            }

            $billingItem->updated_by = $userId;
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
