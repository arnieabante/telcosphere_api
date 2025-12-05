<?php

namespace App\Http\Requests\Api\BillingItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseBillingItemRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'billingItemName' => 'billing_item_name',
            'billingItemQuantity' => 'billing_item_quantity',
            'billingItemRemark' => 'billing_item_remark',
            'billingItemAmount' => 'billing_item_amount',
            'billingItemTotal' => 'billing_item_total',
            'billingStatus' => 'billing_status',
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
