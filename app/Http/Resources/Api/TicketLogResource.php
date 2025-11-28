<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'ticketlog',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'userId' => $this->user_id,
                'ticketId' => $this->ticket_id,
                'action' => $this->action,
                'oldValue' => $this->old_value,
                'newValue' => $this->new_value,
                'note' => $this->note,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('ticketlogs.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'ticketlog' => route('ticketlogs.show', $this->id)
            ]
        ];
    }
}
