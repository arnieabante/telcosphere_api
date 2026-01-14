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
                'invoiceNumber' => $this->invoice_number,
                'billingType' => $this->billing_type,
                'billingDate' => $this->billing_date,
                'billingRemarks' => $this->billing_remarks,
                'billingTotal' => $this->billing_total,
                'billingOffset' => $this->billing_offset,
                'billingBalance' => $this->billing_balance,
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
                    return BillingItemResource::collection($this->billingItems);
                })
            ],
            'links' => [
                'billing' => route('billings.show', $this->uuid)
            ]
        ];
    }
}
