<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;

class PaymentItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * Default attribute values
     */
    protected $attributes = [
        'is_active' => 1
    ];

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'site_id',
        'payment_id',
        'billing_id',
        'billing_item_id',
        'particulars',
        'amount',
        'amount_paid',
        'amount_balance',
        'is_active'
    ];


    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a payment item
        static::creating(function ($paymentItem) {
            $paymentItem->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $paymentItem->created_by = auth()->id();
                $paymentItem->updated_by = auth()->id();
            }
        });

        static::updating(function ($paymentItem) {
            if (auth()->check()) {
                $paymentItem->updated_by = auth()->id();
            }
        });
    }


    /**
     * Use UUID for route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Define which columns should generate UUIDs
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
