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
            'billingItemName' => 'required|string|min:5',
            'billingItemQuantity' => 'required|numeric',
            'billingItemRemark' => 'required|string|min:5',
            'billingItemAmount' => 'required|numeric|decimal:2',
            'billingItemTotal' => 'required|numeric|decimal:2',
            'isActive' => 'required|boolean'
        ];
    }
}
