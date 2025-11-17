<?php

namespace App\Http\Requests\Api\ClientRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends BaseClientRequest
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
            'middleName' => 'nullable|string|min:2',
            'lastName' => 'required|string|min:2',
            'mobileNo' => 'string|min:11',
            'email' => 'nullable|string|email|unique:clients',
            'houseNo' => 'string|min:5',
            'accountNo' => 'nullable|string', 
            'installationDate' => 'required|string',
            'installationFee' => 'nullable|string',
            'balanceFromPrevBilling' => 'nullable|string',
            'prorateFee' => 'string',
            'prorateFeeRemarks' => 'nullable|string',
            'prorateFeeStatus' => 'nullable|string',
            'inactiveDate' => 'string|min:5',
            'notes' => 'nullable|string|min:2',
            'facebookProfileUrl' => 'nullable|string|min:5',
            'billingCategoryId' => 'required|string',
            'serverId' => 'required|string',
            'internetPlanId' => 'required|string',
            'lastAutoBillingDate' => 'nullable|string',
            'isActive' => 'required|string'
        ];
    }
}
