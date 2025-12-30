<?php

namespace App\Http\Requests\Api\ExpenseItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseExpenseItemRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'expenseId' => 'expense_id',
            'expenseCategory' => 'expense_category',
            'expenseRemark' => 'expense_remark',
            'expenseAmount' => 'amount',
            'isActive' => 'is_active'
        ];

        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
