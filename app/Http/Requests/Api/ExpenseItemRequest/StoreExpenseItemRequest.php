<?php

namespace App\Http\Requests\Api\ExpenseItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseItemRequest extends BaseExpenseItemRequest
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
            'expenseId' => 'required|numeric',
            'expenseCategory' => 'required|numeric',
            'expenseRemark' => 'required|string|min:5',
            'expenseAmount' => 'required|numeric|decimal:2',
            'isActive' => 'required|boolean'
        ];
    }
}
