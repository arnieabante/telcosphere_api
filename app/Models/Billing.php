<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    /** @use HasFactory<\Database\Factories\BillingFactory> */
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
        'site_id' => 1,
        'is_active' => 1,
        'created_by' => 1, // TODO
        'updated_by' => 1 // TODO
    ];

    protected $fillable = [
        'is_active',
        'client_id',
        'invoice_number',
        'billing_type',
        'billing_date',
        'billing_description',
        'billing_remarks',
        'billing_total',
        'billing_offset',
        'billing_balance',
        'billing_status',
        'billing_cutoff',
        'disconnection_date'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a billing
        static::creating(function ($billing) {
            $billing->site_id = $billing->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
        });
    }

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function billingItems(): HasMany {
        return $this->hasMany(BillingItem::class);
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class);
    }
}
