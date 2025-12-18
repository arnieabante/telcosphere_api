<?php

namespace App\Http\Requests\Api\EmployeeRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseEmployeeRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'firstName' => 'firstname',
            'middleName' => 'middlename',
            'lastName' => 'lastname',
            'birthDate' => 'birth_date',
            'gender' => 'gender',
            'civilStatus' => 'civil_status',
            'emailAddress' => 'email_address',
            'contactNo' => 'contact_no',
            'homeAddress' => 'home_address',
            'emergencyContactNo' => 'emergency_contact_no',
            'employeeId' => 'employee_id',
            'dateHired' => 'date_hired',
            'department' => 'department',
            'designation' => 'designation',
            'workLocation' => 'work_location',
            'accessLevel' => 'access_level',
            'user_id' => 'user_id',
            'shiftScheduleFrom' => 'shift_schedule_from',
            'shiftScheduleTo' => 'shift_schedule_to',
            'salaryRatePerDay' => 'salary_rate_per_day',
            'hourlyRatePerDay' => 'hourly_rate_per_day',
            'paymentMethod' => 'payment_method',
            'bankName' => 'bank_name',
            'bankAccountNo' => 'bank_account_no',
            'sssNo' => 'sss_no',
            'sssAmount' => 'sss_amount',
            'pagibigNo' => 'pagibig_no',
            'pagibigAmount' => 'pagibig_amount',
            'philhealthNo' => 'philhealth_no',
            'philhealthAmount' => 'philhealth_amount',
            'tin' => 'tin',
            'employeeType' => 'employee_type',
            'isActive' => 'is_active',
        ];
        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
