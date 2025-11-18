<?php

namespace App\Http\Requests\Api\EmployeeRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceEmployeeRequest extends BaseEmployeeRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => 'required|string|min:2',
            'middleName' => 'string|min:2',
            'lastName' => 'required|string|min:2',
            'birthDate' => 'string|min:2',
            'gender' => 'string|min:2',
            'civilStatus' => 'string|min:2',
            'emailAddress' => 'string|email|unique:employees',
            'contactNo' => 'string|min:2',
            'homeAddress' => 'string|min:2',
            'emergencyContactNo' => 'required|string|min:2',
            'employeeId' => 'string|employee_id|unique:employees',
            'dateHired' => 'required|string|min:2',
            'department' => 'required|string|min:2',
            'designation' => 'required|string|min:2',
            'workLocation' => 'string|min:2',
            'accessLevel' => 'required|string|min:2',
            'userId' => 'string|user_id|unique:employees',
            'shiftScheduleFrom' => 'required|string|min:2',
            'shiftScheduleTo' => 'required|string|min:2',
            'salaryRatePerDay' => 'required|string|min:2',
            'hourlyRatePerDay' => 'required|string|min:2',
            'paymentMethod' => 'required|string|min:2',
            'bankName' => 'required|string|min:2',
            'bankAccountNo' => 'required|bank_account_no|unique:employees',
            'sssNo' => 'required|sss_no|unique:employees',
            'pagibigNo' => 'required|pagibig_no|unique:employees',
            'philhealthNo' => 'required|philhealth_no|unique:employees',
            'tin' => 'required|tin|unique:employees',
            'employeeType' => 'required|string|min:2',
            'isActive' => 'is_active',
        ];
    }
}
