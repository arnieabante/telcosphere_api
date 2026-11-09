<?php

namespace App\Http\Requests\Api\ClientRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends BaseClientRequest
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
            'email' => ['nullable', 'string', 'email', Rule::unique('clients')->where(fn ($query) => $query->where('site_id', auth()->user()->site_id))],
            'houseNo' => 'string|min:5',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'accountNo' => 'required|string',
            'installationDate' => 'required|string',
            'installationFee' => 'nullable|string',
            'balanceFromPrevBilling' => 'nullable|numeric',
            'balanceFromPrevBillingStatus' => 'nullable|string',
            'currentBalance' => 'nullable|numeric',
            'prorateFee' => 'numeric',
            'prorateStartDate' => 'nullable|string',
            'prorateEndDate' => 'nullable|string',
            'prorateFeeRemarks' => 'nullable|string',
            'prorateFeeStatus' => 'nullable|string',
            'inactiveDate' => 'string|min:5',
            'notes' => 'nullable|string|min:2',
            'facebookProfileURL' => 'nullable|string|min:5',
            'billingCategoryId' => 'required|int',
            'serverId' => 'required|int',
            'internetPlanId' => 'required|int',
            'prevInternetPlanId' => 'nullable|int',
            'lastAutoBillingDate' => 'nullable|string',
            'isActive' => 'required|string'
        ] + $this->networkRules();
    }
}
