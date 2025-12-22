<?php

namespace App\Http\Requests\Api\ClientRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceClientRequest extends BaseClientRequest
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
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'accountNo' => 'nullable|string', 
            'installationDate' => 'required|string',
            'installationFee' => 'string',
            'balanceFromPrevBilling' => 'numeric',
            'prorateFee' => 'numeric',
            'prorateStartDate' => 'nullable|string',
            'prorateEndDate' => 'nullable|string',
            'prorateFeeRemarks' => 'string',
            'prorateFeeStatus' => 'string',
            'inactiveDate' => 'string|min:5',
            'notes' => 'nullable|string|min:2',
            'facebookProfileUrl' => 'nullable|string|min:5',
            'billingCategoryId' => 'required|int',
            'serverId' => 'required|int',
            'internetPlanId' => 'required|int',
            'last_auto_billing_date' => 'string',
            'isActive' => 'required|string'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
