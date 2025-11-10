<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
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
        'first_name',
        'middle_name',
        'last_name',
        'mobile_no',
        'email',
        'house_no',
        'account_no',
        'installation_date',
        'inactive_date',
        'notes',
        'facebook_profile_url',
        'billing_category_id',
        'server_id',
        'internet_plan_id',
        'is_active',
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
    public function internetPlan()
    {
        return $this->belongsTo(\App\Models\InternetPlan::class, 'internet_plan_id');
    }

    public function billingCategory()
    {
        return $this->belongsTo(\App\Models\BillingCategory::class, 'billing_category_id');
    }
}
