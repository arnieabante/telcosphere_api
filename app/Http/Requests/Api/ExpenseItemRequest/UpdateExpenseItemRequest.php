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
        $data = [
            'updated_by' => 1,
            'updated_at' => now(),
        ];

        if ($this->has('isActive')) {
            $data['is_active'] = $this->boolean('isActive');
            return $data;
        }

        if ($this->has('expenseCategory')) {
            $data['expense_category'] = $this->expenseCategory;
        }

        if ($this->has('expenseRemark')) {
            $data['remark'] = $this->expenseRemark;
        }

        if ($this->has('expenseAmount')) {
            $data['amount'] = $this->expenseAmount;
        }

        return $data;
    }

}
