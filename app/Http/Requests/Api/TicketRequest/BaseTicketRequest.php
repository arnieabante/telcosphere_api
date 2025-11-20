<?php

namespace App\Http\Requests\Api\TicketRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseTicketRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'clientId' => 'client_id',
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
