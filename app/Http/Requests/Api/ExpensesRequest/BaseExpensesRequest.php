<?php

namespace App\Http\Requests\Api\ExpensesRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseExpensesRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'expenseDate' => 'expense_date',
            'staffName' => 'staff_name',
            'total' => 'total',
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
