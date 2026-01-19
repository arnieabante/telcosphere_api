<?php

namespace App\Http\Requests\Api\BillingRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingRequest extends BaseBillingRequest
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
            'billingDate' => 'required|date',
            'billingRemarks' => 'required|string|max:100|nullable',
            'billingTotal' => 'required|digits_between:1,8|decimal:0,2',
            'billingOffset' => 'required|digits_between:1,8|decimal:0,2',
            'billingBalance' => 'required|digits_between:1,8|decimal:0,2',
            'billingStatus' => 'required|string'
        ];
    }
}
