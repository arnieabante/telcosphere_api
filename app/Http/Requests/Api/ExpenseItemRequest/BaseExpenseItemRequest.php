<?php

namespace App\Http\Requests\Api\ExpenseItemRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BaseExpenseItemRequest extends FormRequest
{
    public function mappedAttributes(): array {
        return collect($this->input('items'))->map(function ($item) {
            return [
                'uuid' => Str::uuid(),
                'expense_id' => $item['expenseId'],
                'expense_category' => $item['expenseCategory'],
                'remark' => $item['expenseRemark'] ?? null,
                'amount' => $item['expenseAmount'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

    }
}
