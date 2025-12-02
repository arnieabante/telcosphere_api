<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Payment; 

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'type' => 'payment',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'receiptNo' => $this->receipt_no,
                'clientName' => optional($this->client)->first_name . " " . optional($this->client)->last_name,
                'paymentDate' => $this->payment_date,
                'paymentName' => $this->payment_name,
                'paymentAmount' => $this->payment_amount,
                'paymentMethod' => $this->payment_method,
                'reference' => $this->reference,
                'collectedBy' => optional($this->collectedBy)->fullname, 
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('payments.show'),
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
                'collectedBy' => new UserResource($this->whenLoaded('collectedBy')),
            ],
            'links' => [
                'payment' => route('payments.show', $this->id),
            ]
        ];
    }
}
