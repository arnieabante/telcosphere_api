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
            'firstName' => 'sometimes|required|string|min:2',
            'middleName' => 'sometimes|nullable|string|min:2',
            'lastName' => 'sometimes|required|string|min:2',
            'mobileNo' => 'sometimes|string|min:11',
            'email' => 'sometimes|nullable|string|email',
            'houseNo' => 'sometimes|string|min:5',
            'latitude' => 'sometimes|nullable|string',
            'longitude' => 'sometimes|nullable|string',
            'accountNo' => 'required|string', 
            'installationDate' => 'sometimes|required|string',
            'installationFee' => 'sometimes|string',
            'balanceFromPrevBilling' => 'sometimes|numeric',
            'currentBalance' => 'sometimes|numeric',
            'prorateFee' => 'sometimes|numeric',
            'prorateStartDate' => 'sometimes|nullable|string',
            'prorateEndDate' => 'sometimes|nullable|string',
            'prorateFeeRemarks' => 'sometimes|string',
            'prorateFeeStatus' => 'sometimes|string',
            'inactiveDate' => 'sometimes|string|min:5',
            'notes' => 'sometimes|nullable|string|min:2',
            'facebookProfileUrl' => 'sometimes|nullable|string|min:5',
            'billingCategoryId' => 'sometimes|required|int',
            'serverId' => 'sometimes|required|int',
            'internetPlanId' => 'sometimes|required|int',
            'prevInternetPlanId' => 'sometimes|nullable|int',
            'last_auto_billing_date' => 'sometimes|string',
            'pppoe_username' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'isActive' => 'required|string'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
