<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'expenseRemark' => $this->remark,
                'expenseAmount' => $this->amount,
                'isActive' => $this->is_active,
            ],
            'relationships' => [
                'expense' => [
                    'uuid' => $this->expense->uuid,
                    'expenseDate' => $this->expense->expense_date,
                    'staffName' => $this->expense->staff_name,
                ],
                'expenseCategory' => [
                    'name' => $this->expenseCategory->name,
                    'description' => $this->expenseCategory->description,
                ],
            ],
        ];
    }

}
