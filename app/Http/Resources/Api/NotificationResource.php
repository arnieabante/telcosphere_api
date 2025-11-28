<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'notification',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'ticketId' => $this->ticket_id,
                'type' => $this->type,
                'message' => $this->message,
                'isRead' => $this->is_read,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('notifications.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                ),
            ],
            'links' => [
                'notification' => route('notifications.show', $this->id)
            ]
        ];
    }
}
