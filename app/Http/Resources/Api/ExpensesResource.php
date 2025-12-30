<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpensesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'expensecategory',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'expense_date' => $this->expense_date,
                'staff_name' => $this->staff_name,
                'total' => $this->total,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('expensecategories.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'expensecategory' => route('expensecategories.show', $this->id)
            ]
        ];
    }
}
