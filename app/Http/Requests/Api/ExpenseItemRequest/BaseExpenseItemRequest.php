<?php

namespace App\Http\Requests\Api\ExpenseItemRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BaseExpenseItemRequest extends FormRequest
{
    public function mappedAttributes(int $expenseId): array
    {
        return collect($this->input('items'))->map(function ($item) use ($expenseId) {
            return [
                'uuid'             => Str::uuid(),
                'expense_id'       => $expenseId,
                'expense_category' => $item['expenseCategory'],
                'remark'           => $item['expenseRemark'] ?? null,
                'amount'           => $item['expenseAmount'],
                'is_active'        => 1,
                'site_id'          => 1,
                'created_by'       => 1,
                'updated_by'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        })->toArray();
    }
}
