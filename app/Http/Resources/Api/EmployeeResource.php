<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'client',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'firstName' => $this->firstname,
                'middleName' => $this->middlename,
                'lastName' => $this->lastname,
                'employeeName' => trim($this->firstname . ' ' . $this->lastname),
                'birthDate' => $this->birth_date,
                'gender' => $this->gender,
                'civilStatus' => $this->civil_status,
                'emailAddress' => $this->email_address,
                'contactNo' => $this->contact_no,
                'homeAddress' => $this->home_address,
                'emergencyContactNo' => $this->emergency_contact_no,
                'employeeId' => $this->employee_id,
                'dateHired' => $this->date_hired,
                'department' => $this->department,
                'designation' => $this->designation,
                'workLocation' => $this->work_location,
                'accessLevel' => $this->access_level,
                'user_id' => $this->user_id,
                'shiftScheduleFrom' => $this->shift_schedule_from,
                'shiftScheduleTo' => $this->shift_schedule_to,
                'salaryRatePerDay' => $this->salary_rate_per_day,
                'hourlyRatePerDay' => $this->hourly_rate_per_day,
                'paymentMethod' => $this->payment_method,
                'bankName' => $this->bank_name,
                'bankAccountNo' => $this->bank_account_no,
                'sssNo' => $this->sss_no,
                'sssAmount' => $this->sss_amount,
                'pagibigNo' => $this->pagibig_no,
                'pagibigAmount' => $this->pagibig_amount,
                'philhealthNo' => $this->philhealth_no,
                'philhealthAmount' => $this->philhealth_amount,
                'tin' => $this->tin,
                'employeeType' => $this->employee_type,
                'roleName' => $this->role?->name,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('employees.show'),
                    [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at,
                    ]
                ),
            ],
           'relationships' => [
                'role' => new RoleResource($this->whenLoaded('role')),
            ],
            'links' => [
                'employee' => route('employees.show', $this->id),
            ],
        ];
    }
}
