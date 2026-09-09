<?php

namespace App\Http\Requests\Api\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplacePaymentRequest extends BasePaymentRequest
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
            'billing_id' => 'required|integer',
            'billingItemId' => 'required|integer',
            'particulars' => 'required|string',
            'amount' => 'required|integer',
            'amountPaid' => 'required|integer',
            'amountBalance' => 'required|integer',
            'isActive' => 'required|string'
        ];
    }
}
