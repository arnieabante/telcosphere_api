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
            'billingItemName' => 'sometimes|required|string|min:5',
            'billingItemQuantity' => 'sometimes|required|numeric',
            'billingItemRemark' => 'sometimes|required|string|min:5',
            'billingItemAmount' => 'sometimes|required|numeric|decimal:2',
            'billingItemTotal' => 'sometimes|required|numeric|decimal:2',
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
