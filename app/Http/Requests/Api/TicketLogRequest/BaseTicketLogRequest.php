<?php

namespace App\Http\Requests\Api\TicketLogRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseTicketLogRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'ticketId' => 'ticket_id',
            'userId' => 'user_id',
            'action' => 'action',
            'oldValue' => 'old_value',
            'newValue' => 'new_value',
            'note' => 'note',
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
