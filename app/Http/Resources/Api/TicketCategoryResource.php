<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'ticketcategory',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'isActive' => $this->is_active, 
                $this->mergeWhen(
                    request()->routeIs('ticketcategories.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'ticketcategory' => route('ticketcategories.show', $this->id)
            ]
        ];
    }
}
