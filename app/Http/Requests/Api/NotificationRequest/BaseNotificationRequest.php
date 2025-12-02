<?php

namespace App\Http\Requests\Api\NotificationRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseNotificationRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'userId' => 'user_id',
            'ticketId' => 'ticket_id',
            'type' => 'type',
            'message' => 'message',
            'isRead' => 'is_read',
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
