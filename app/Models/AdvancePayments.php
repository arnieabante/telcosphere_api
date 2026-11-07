<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class AdvancePayments extends Model
{
    /** @use HasFactory<\Database\Factories\BillingFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'site_id',
        'client_id',
        'amount'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a advancepayments
        static::creating(function ($advancepayments) {
            // but only when site_id is not already set
            if (empty($advancepayments->site_id)) {
                $advancepayments->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $advancepayments->created_by = auth()->id();
                $advancepayments->updated_by = auth()->id();
            }
        });

        static::updating(function ($advancepayments) {
            if (auth()->check()) {
                $advancepayments->updated_by = auth()->id();
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

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class);
    }
}
