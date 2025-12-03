<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'billing',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'clientId' => $this->client_id,
                'billingDate' => $this->billing_date,
                'billingRemarks' => $this->billing_remarks,
                'billingTotal' => $this->billing_total,
                'billingStatus' => $this->billing_status,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('billing.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                )
            ],
            'relationships' => [
                'client' => new ClientResource($this->whenLoaded('client')),
                'billingItems' => $this->whenLoaded('billingItems', function () {
                    return [
                        'billingItems' => BillingItemResource::collection($this->billingItems)
                    ];
                })
            ],
            'links' => [
                'billing' => route('billings.show', $this->uuid)
            ]
        ];
    }
}
