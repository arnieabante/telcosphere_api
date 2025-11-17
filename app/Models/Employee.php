<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
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
        'firstname',
        'middlename',
        'lastname',
        'birth_date',
        'gender',
        'civil_status',
        'email_address',
        'contact_no',
        'home_address',
        'emergency_contact_no',
        'employee_id',
        'date_hired',
        'department',
        'designation',
        'work_location',
        'access_level',
        'user_id',
        'shift_schedule_from',
        'shift_schedule_to',
        'salary_rate_per_day',
        'hourly_rate_per_day',
        'payment_method',
        'bank_name',
        'bank_account_no',
        'sss_no',
        'pagibig_no',
        'philhealth_no',
        'tin',
        'employee_type',
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
    // public function internetPlan()
    // {
    //     return $this->belongsTo(\App\Models\Internetplan::class, 'internet_plan_id');
    // }

    // public function billingCategory()
    // {
    //     return $this->belongsTo(\App\Models\BillingCategory::class, 'billing_category_id');
    // }
}
