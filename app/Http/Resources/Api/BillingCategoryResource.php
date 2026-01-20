<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'billingcategory',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'dateCycle' => $this->date_cycle,
                'daysToDueDate' => $this->days_to_due_date,
                'daysToDisconnectionDate' => $this->days_to_disconnection_date,
                'isActive' => $this->is_active, 
                $this->mergeWhen(
                    request()->routeIs('billingcategories.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'billingcategory' => route('billingcategories.show', $this->id)
            ]
        ];
    }
}
