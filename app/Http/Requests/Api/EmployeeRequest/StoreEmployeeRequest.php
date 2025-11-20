<?php

namespace App\Http\Requests\Api\EmployeeRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends BaseEmployeeRequest
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
            'birthDate' => 'required|string|min:2',
            'gender' => 'nullable|string',
            'civilStatus' => 'string',
            'emailAddress' => 'string|email|unique:employees,email_address',
            'contactNo' => 'string|min:2',
            'homeAddress' => 'required|string|min:2',
            'emergencyContactNo' => 'required|string|min:2',
            'employeeId' => 'nullable|string|unique:employees,employee_id',
            'dateHired' => 'required|string|min:2',
            'department' => 'required|string|min:2',
            'designation' => 'required|string|min:2',
            'workLocation' => 'nullable|string|min:2',
            'accessLevel' => 'required|string',
            'userId' => 'nullable|string|unique:employees,user_id',
            'shiftScheduleFrom' => 'nullable|string|min:2',
            'shiftScheduleTo' => 'nullable|string|min:2',
            'salaryRatePerDay' => 'required|string|min:2',
            'hourlyRatePerDay' => 'required|string|min:2',
            'paymentMethod' => 'nullable|string|min:2',
            'bankName' => 'nullable|string|min:2',
            'bankAccountNo' => 'nullable|string|unique:employees,bank_account_no',
            'sssNo' => 'required|string|unique:employees,sss_no',
            'pagibigNo' => 'required|string|unique:employees,pagibig_no',
            'philhealthNo' => 'required|string|unique:employees,philhealth_no',
            'tin' => 'nullable|string|string|unique:employees,tin',
            'employeeType' => 'required|string',
            'isActive' => 'is_active',
        ];
    }
}
