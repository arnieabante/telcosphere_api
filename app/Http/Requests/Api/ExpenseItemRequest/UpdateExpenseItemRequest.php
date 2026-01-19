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
            'expenseCategory' => 'sometimes|integer|exists:expense_categories,id',
            'expenseRemark'   => 'nullable|string',
            'expenseAmount'   => 'sometimes|numeric|min:0',
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }

    public function mappedAttributes(): array
    {

        if ($this->has('isActive')) {
            return [
                'is_active'  => $this->boolean('isActive'),
                'updated_by'=> 1,
                'updated_at'=> now(),
            ];
        }

        return [
            'expense_category' => $this->expenseCategory,
            'remark'           => $this->expenseRemark,
            'amount'           => $this->expenseAmount,
            'updated_by'       => 1,
            'updated_at'       => now(),
        ];
    }
}
