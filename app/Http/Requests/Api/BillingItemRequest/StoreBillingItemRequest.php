<?php

namespace App\Http\Requests\Api\BillingItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingItemRequest extends BaseBillingItemRequest
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
            'billing.billingItems.*.billingItemName' => 'required_if:billing.billingType,2,3,4|string|min:5|max:100',
            'billing.billingItems.*.billingItemParticulars' => 'required_if:billing.billingType,2,3,4|string|min:5|max:100',
            'billing.billingItems.*.billingItemPrice' => 'required_if:billing.billingType,2,3,4|numeric|min:1|max:999999.99|decimal:0,2',
            'billing.billingItems.*.billingItemQuantity' => 'required|numeric|min:1|max:999',
            'billing.billingItems.*.billingItemRemark' => 'string|min:5|nullable',
        ];
    }

    public function messages(): array 
    {
        return [
            'billing.billingItems.*.billingItemName.required_if' => 'Category or Item Name is required.',
            'billing.billingItems.*.billingItemName.*' => 'Category or Item Name is too short or too long.',
            'billing.billingItems.*.billingItemParticulars.required_if' => 'Particulars is required.',
            'billing.billingItems.*.billingItemParticulars.*' => 'Particulars is too short or too long.',
            'billing.billingItems.*.billingItemPrice.decimal' => 'Price should have 2 decimal places only.',
            'billing.billingItems.*.billingItemPrice.*' => 'Price should be between the range of 1.00 to 999,999.99',
            'billing.billingItems.*.billingItemQuantity.*' => 'Quantity should be between the range of 1 and 999.'
        ];
    }
}
