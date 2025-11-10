<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'site_id' => 1,
       'is_active' => 1,
       'created_by' => 99,
       'updated_by' => 99
    ];

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
        'is_active'
    ];

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function internetPlan()
    {
        return $this->belongsTo(internetPlan::class, 'internet_plan_id'); 
    }

    public function billingCategory()
    {
        return $this->belongsTo(billingCategory::class, 'billing_category_id');
    }
}
