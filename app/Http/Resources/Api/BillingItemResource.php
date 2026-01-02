<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'billingitem',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'billingId' => $this->billing_id,
                'billingItemName' => $this->billing_item_name,
                'billingItemQuantity' => $this->billing_item_quantity,
                'billingItemPrice' => $this->billing_item_price,
                'billingItemRemark' => $this->billing_item_remark,
                'billingItemAmount' => $this->billing_item_amount,
                'billingItemTotal' => $this->billing_item_total,
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
            'links' => [
                'billingitem' => route('billingitems.show', $this->uuid)
            ]
        ];
    }
}
