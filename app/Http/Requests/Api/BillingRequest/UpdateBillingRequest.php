<?php

namespace App\Http\Requests\Api\BillingRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingRequest extends BaseBillingRequest
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
            'billing.billingType' => 'sometimes|required|string',
            // 'billing.billingCutoff' => 'sometimes|required_if:billing.billingType,1|date',
            // 'billing.disconnectionDate' => 'sometimes|required_if:billing.billingType,1|date',
            'billing.clientId' => 'sometimes|required|string',
            'billing.billingDescription' => 'sometimes|required|string|min:5|max:100|nullable',
            'billing.billingRemarks' => 'sometimes|string|min:5|max:100|nullable',
            'billing.isActive' => 'required|boolean'
        ];
    }

    public function messages(): array 
    {
        return [
            'billing.billingType.required' => 'Billing or Invoice Type is required.',
            // 'billing.billingCutoff.required_if' => 'Billing Cut-off Date is required.',
            // 'billing.disconnectionDate.required_if' => 'Disconnection Date is required.',
            'billing.clientId.required' => 'Client is required.',
            'billing.billingDescription.required' => 'Bill To / Description is required.',
        ];
    }
}
