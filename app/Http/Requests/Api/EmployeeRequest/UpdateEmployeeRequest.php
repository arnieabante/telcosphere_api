<?php

namespace App\Http\Requests\Api\EmployeeRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends BaseEmployeeRequest
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
            'firstName' => 'sometimes|string|min:2',
            'middleName' => 'string',
            'lastName' => 'sometimes|string|min:2',
            'birthDate' => 'nullable|string|min:2',
            'gender' => 'nullable|int',
            'civilStatus' => 'nullable|int',
            'emailAddress' => 'sometimes|nullable|string|email|min:5',
            'contactNo' => 'nullable|string|min:2',
            'homeAddress' => 'nullable|string|min:2',
            'emergencyContactNo' => 'nullable|string|min:2',
            'employeeId' => 'sometimes|nullable|string|min:5',
            'dateHired' => 'sometimes|string|min:2',
            'department' => 'nullable|string|min:2',
            'designation' => 'nullable|string|min:2',
            'workLocation' => 'nullable|string|min:2',
            'accessLevel' => 'nullable|sometimes|string',
            'user_id' => 'nullable|sometimes|numeric',
            'shiftScheduleFrom' => 'nullable|string|min:2',
            'shiftScheduleTo' => 'nullable|string|min:2',
            'salaryRatePerDay' => 'sometimes|numeric|min:2',
            'hourlyRatePerDay' => 'sometimes|numeric|min:2',
            'paymentMethod' => 'sometimes|numeric',
            'bankName' => 'nullable|string|min:2',
            'bankAccountNo' => 'sometimes|nullable|string|min:2',
            'sssNo' => 'sometimes|nullable|string|min:2',
            'sssAmount' => 'sometimes|nullable|string|min:2',
            'pagibigNo' => 'sometimes|nullable|string|min:2',
            'pagibigAmount' => 'sometimes|nullable|string|min:2',
            'philhealthNo' => 'sometimes|nullable|string|min:2',
            'philhealthAmount' => 'sometimes|nullable|string|min:2',
            'tin' => 'sometimes|nullable|string|string|min:2',
            'employeeType' => 'sometimes|string',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
