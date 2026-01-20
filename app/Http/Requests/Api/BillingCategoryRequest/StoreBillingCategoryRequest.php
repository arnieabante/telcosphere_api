<?php

namespace App\Http\Requests\Api\BillingCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingCategoryRequest extends BaseBillingCategoryRequest
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
            'name' => 'required|string|min:3|unique:billing_categories',
            'description' => 'nullable|string|min:3|max:100',
            'dateCycle' => 'required|integer',
            'daysToDueDate' => 'required|integer',
            'daysToDisconnectionDate' => 'required|integer',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
