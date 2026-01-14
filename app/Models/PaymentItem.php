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
        'site_id' => 1,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ];

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
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

        // Auto-assign site_id when creating a ticket
        static::creating(function ($ticket) {
            $ticket->site_id = $ticket->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
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
