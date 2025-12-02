<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;

class Payment extends Model
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
        'client_id',
        'receipt_no',
        'payment_amount',
        'payment_method',
        'reference',
        'discount',
        'discount_total',
        'discount_reason',
        'payment_date',
        'collected_by',
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

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }
}
