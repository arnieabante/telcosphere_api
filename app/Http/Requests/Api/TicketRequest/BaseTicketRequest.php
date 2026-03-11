<?php

namespace App\Http\Requests\Api\TicketRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseTicketRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'ticketType' => 'ticket_type',
            'clientId' => 'client_id',
            'requestorName' => 'requestor_name',
            'requestorLocation' => 'requestor_location',
            'name' => 'name',
            'description' => 'description',
            'categoryId' => 'category_id',
            'requestedDate' => 'requested_date',
            'dueDate' => 'due_date',
            'assignedTo' => 'assigned_to',
            'remarks' => 'remarks',
            'status' => 'status',
            'isActive' => 'is_active'
        ];

        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
