<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
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
        'name',
        'description',
        'category_id',
        'requested_date',
        'due_date',
        'assigned_to',
        'status',
        'is_active'
    ];

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

    public function ticketCategory()
    {
        return $this->belongsTo(\App\Models\BillingCategory::class, 'category_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }
}
