<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Ticket; 

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'type' => 'ticket',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'clientName' => optional($this->client)->first_name . " " . optional($this->client)->last_name,
                'name' => $this->name,
                'description' => $this->description,
                'category' => optional($this->ticketCategory)->name,
                'requestedDate' => $this->requested_date,
                'dueDate' => $this->due_date,
                'assignedTo' => optional($this->assignedTo)->fullname, 
                'remarks' => $this->remarks,
                'status' => $this->status,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('tickets.show'),
                    [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at,
                    ]
                ),
            ],
            'relationships' => [
                'client' => new ClientResource($this->whenLoaded('client')),
                'ticketCategory' => new TicketCategoryResource($this->whenLoaded('ticketCategory')),
                'assignedTo' => new UserResource($this->whenLoaded('assignedTo')),
            ],
            'links' => [
                'ticket' => route('tickets.show', $this->id),
            ]
        ];
    }
}
