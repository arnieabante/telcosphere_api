<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\ExpenseItemResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'expenses',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'expenseDate' => $this->expense_date,
                'staffName' => $this->staff_name,
                'total' => $this->total,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('expenses.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'relationships' => [
                'expenseItems' => ExpenseItemResource::collection(
                    $this->whenLoaded('expenseItems')
                ),
            ],
            'links' => [
                'expense' => route('expenses.show', $this->id)
            ]
        ];
    }
}
