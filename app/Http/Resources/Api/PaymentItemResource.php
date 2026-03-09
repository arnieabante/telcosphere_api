<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'paymentitem',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'billing_item_id' => $this->billing_item_id,
                'particulars' => $this->particulars,
                'amount' => $this->amount,
                'amount_paid' => $this->amount_paid,
                'amount_balance' => $this->amount_balance,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('paymentitems.show'),
                    [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at,
                    ]
                ),
            ],
            'links' => []
        ];
    }
}
