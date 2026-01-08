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
            'type' => 'expenseitem',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'expenseId' => $this->expense_id,
                'expenseRemark' => $this->remark,
                'expenseAmount' => $this->amount,
                'isActive' => $this->is_active,
                'expenseCategory' => [
                    'id' => $this->expenseCategory->id,
                    'name' => $this->expenseCategory->name,
                    'description' => $this->expenseCategory->description,
                ],
                $this->mergeWhen(
                    request()->routeIs('expenseitem.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'expenseitem' => route('expenseitems.show', $this->id)
            ]
        ];
    }
}
