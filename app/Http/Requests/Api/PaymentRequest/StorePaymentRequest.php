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
            'clientId' => 'required|number',
            'receiptNo' => 'required|string',
            'paymentName' => 'required|string|max:50',
            'paymentAmount' => 'required|number',
            'paymentMethod' => 'required|string',
            'reference' => 'string|nullable',
            'discount' => 'required|number',
            'discount_total' => 'required|number',
            'discount_reason' => 'required|string',
            'paymentDate' => 'nullable|string',
            'collectedBy' => 'nullable|number', 
            'isActive' => 'required|string'
        ];
    }
}
