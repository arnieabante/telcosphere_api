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
            'billingDate' => 'sometimes|required|date',
            'billingDescription' => 'sometimes|required|string|max:100|nullable',
            'billingRemarks' => 'sometimes|required|string|max:100|nullable',
            'billingTotal' => 'sometimes|required|digits_between:1,8|decimal:0,2',
            'billingOffset' => 'sometimes|required|digits_between:1,8|decimal:0,2',
            'billingBalance' => 'sometimes|required|digits_between:1,8|decimal:0,2',
            'billingStatus' => 'sometimes|required|string'
        ];
    }
}
