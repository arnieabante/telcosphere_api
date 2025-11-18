<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'billing_date',
        'billing_remarks',
        'billing_total',
        'billing_status'
    ];

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }
}
