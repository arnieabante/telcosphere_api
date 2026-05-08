<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Billing extends Model
{
    /** @use HasFactory<\Database\Factories\BillingFactory> */
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
        'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
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
        'balance_from_prev_billing',
        'billing_cutoff',
        'disconnection_date'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a billing
        static::creating(function ($billing) {
            // but only when site_id is not already set
            if (empty($billing->site_id)) {
                $billing->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            $userId = auth()->id();
            if (!$userId) {
                $userId = User::where('site_id', $billing->site_id)
                    ->whereHas('role', function ($q) {
                        $q->where('description', 'Administrator');
                    })
                    ->value('id');
            }

            $billing->created_by = $userId;
            $billing->updated_by = $userId;
        });

        static::updating(function ($billing) {

            $userId = auth()->id();
            if (!$userId) {
                $userId = User::where('site_id', $billing->site_id)
                    ->whereHas('role', function ($q) {
                        $q->where('description', 'Administrator');
                    })
                    ->value('id');
            }

            $billing->updated_by = $userId;
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
