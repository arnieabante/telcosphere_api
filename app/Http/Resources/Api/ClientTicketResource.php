<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'ticket',
            'attributes' => [
                'uuid' => $this->uuid,
                'requestorName' => $this->requestor_name,
                'address' => $this->client?->house_no ?? $this->requestor_location,
                'name' => $this->name,
                'description' => $this->description,
                'category' => $this->ticketCategory?->name,
                'requestedDate' => $this->requested_date,
                'dueDate' => $this->due_date,
                'assignedTo' => $this->assignedTo?->fullname,
                'remarks' => $this->remarks,
                'status' => $this->status,
            ],
        ];
    }
}
