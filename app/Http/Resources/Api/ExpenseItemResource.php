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
                'expense_id' => $this->expense_date,
                'expense_remark' => $this->staff_name,
                'amount' => $this->total,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('expenseitems.show'), [
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
