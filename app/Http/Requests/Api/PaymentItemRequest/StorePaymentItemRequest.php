<?php

namespace App\Http\Requests\Api\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends BasePaymentRequest
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
            'billing_id' => 'required|number',
            'billingItemId' => 'required|number',
            'particulars' => 'required|string',
            'amount' => 'required|number',
            'amountPaid' => 'required|number',
            'amountBalance' => 'required|number',
            'isActive' => 'required|string'
        ];
    }
}
