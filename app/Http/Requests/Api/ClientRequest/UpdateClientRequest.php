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
            'firstName' => 'sometimes|required|string|min:2',
            'middleName' => 'sometimes|nullable|string|min:2',
            'lastName' => 'sometimes|required|string|min:2',
            'mobileNo' => 'sometimes|string|min:11',
            'email' => 'sometimes|nullable|string|email|unique:clients',
            'houseNo' => 'sometimes|string|min:5',
            'latitude' => 'sometimes|nullable|string',
            'longitude' => 'sometimes|nullable|string',
            'accountNo' => 'sometimes|nullable|string', 
            'installationDate' => 'sometimes|required|string',
            'installationFee' => 'sometimes|nullable|string',
            'balanceFromPrevBilling' => 'sometimes|nullable|numeric',
            'currentBalance' => 'sometimes|nullable|numeric',
            'prorateFee' => 'sometimes|numeric',
            'prorateStartDate' => 'sometimes|nullable|string',
            'prorateEndDate' => 'sometimes|nullable|string',
            'prorateFeeRemarks' => 'sometimes|nullable|string',
            'prorateFeeStatus' => 'sometimes|nullable|string',
            'inactiveDate' => 'sometimes|string|min:5',
            'notes' => 'sometimes|nullable|string|min:2',
            'facebookProfileUrl' => 'sometimes|nullable|string|min:5',
            'billingCategoryId' => 'sometimes|required|int',
            'serverId' => 'sometimes|required|int',
            'internetPlanId' => 'sometimes|required|int',
            'prevInternetPlanId' => 'sometimes|nullable|int',
            'lastAutoBillingDate' => 'sometimes|nullable|string',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
