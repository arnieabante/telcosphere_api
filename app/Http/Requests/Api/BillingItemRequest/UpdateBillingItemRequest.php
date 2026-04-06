<?php

namespace App\Http\Requests\Api\BillingItemRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingItemRequest extends BaseBillingItemRequest
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
            'billing.billingItems' => 'required|array|min:1',
            'billing.billingItems.*.category' => 'sometimes|required_if:billing.billingType,2,3,4|string|min:5|max:100',
            'billing.billingItems.*.particulars' => 'sometimes|required_if:billing.billingType,2,3,4|string|min:5|max:100',
            'billing.billingItems.*.qty' => 'sometimes|required|numeric|min:1|max:999',
            'billing.billingItems.*.price' => 'sometimes|required_if:billing.billingType,2,3,4|numeric|min:1|max:999999.99|decimal:0,2'
        ];
    }

    public function messages(): array 
    {
        return [
            'billing.billingItems.*.category.required_if' => 'Category or Item Name is required.',
            'billing.billingItems.*.category.*' => 'Category or Item Name is too short or too long.',
            'billing.billingItems.*.particulars.required_if' => 'Particulars is required.',
            'billing.billingItems.*.particulars.*' => 'Particulars is too short or too long.',
            'billing.billingItems.*.qty.*' => 'Quantity should be between the range of 1 and 999.',
            'billing.billingItems.*.price.decimal' => 'Price should have 2 decimal places only.',
            'billing.billingItems.*.price.*' => 'Price should be between the range of 1.00 to 999,999.99'
        ];
    }
}
