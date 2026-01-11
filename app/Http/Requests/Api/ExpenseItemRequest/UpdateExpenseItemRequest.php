<?php

namespace App\Http\Requests\Api\ExpenseItemRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseItemRequest extends FormRequest
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
            'expenseCategory' => 'required|integer|exists:expense_categories,id',
            'expenseRemark'   => 'nullable|string',
            'expenseAmount'   => 'required|numeric|min:0',
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }

    public function mappedAttributes(): array
    {
        return [
            'expense_category' => $this->expenseCategory,
            'remark'           => $this->expenseRemark,
            'amount'           => $this->expenseAmount,
            'updated_by'       => 1,
            'updated_at'       => now(),
        ];
    }
}
