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
                'collectionDate' => $this->collection_date,
                'paymentMethod' => $this->payment_method,
                'reference' => $this->reference,
                'subTotal' => $this->subtotal,
                'discount' => $this->discount,
                'total' => $this->total,
                'amountReceived' => $this->amount_received,
                'amountChange' => $this->amount_change,
                'amountPaid' => $this->amount_paid,
                'discountReason' => $this->discount_reason,
                'collectionDate' => $this->collection_date,
                'collectedBy' => $this->collected_by,
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
