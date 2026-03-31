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
            'billing.billingItems.*.billingItemName' => 'required_if:billing_type,2,3,4|string|min:5',
            'billing.billingItems.*.billingItemParticulars' => 'required_if:billing_type,2,3,4|string|min:5',
            'billing.billingItems.*.billingItemQuantity' => 'required|numeric',
            'billing.billingItems.*.billingItemRemark' => 'string|min:5|nullable',
            'billing.billingItems.*.billingItemPrice' => 'required_if:billing_type,2,3,4|digits_between:1,8|decimal:0,2',
            'billing.billingItems.*.billingItemAmount' => 'required_if:billing_type,2,3,4|digits_between:1,8|decimal:0,2'
        ];
    }
}
