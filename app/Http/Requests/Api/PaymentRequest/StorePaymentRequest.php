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
            'clientId' => 'required|int',
            'collectionDate' => 'nullable|string',
            'collectedBy' => 'nullable|string', 
            'paymentMethod' => 'required|string|max:50',
            'reference' => 'string|nullable',
            'subtotal' => 'required|decimal:2',
            'discount' => 'decimal:2|nullable',
            'total' => 'required|decimal:2',
            'amountReceived' => 'required|decimal:2',
            'amountChange' => 'decimal:2|nullable',
            'amountPaid' => 'decimal:2|nullable',
            'discount_reason' => 'nullable|string',
            'balance' => 'decimal:2|nullable',
            'isActive' => 'required|string'
        ];
    }
}
