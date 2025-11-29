<?php

namespace App\Http\Requests\Api\BillingRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseBillingRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'billingDate' => 'billing_date',
            'billingRemarks' => 'billing_remarks',
            'billingTotal' => 'billing_total',
            'billingStatus' => 'billing_status',

            // not from Model
            'billingType' => 'billing_type',
            'billingCategory' => 'billing_category',

            /*
            // Billing Item fields
            'billingItemName' => 'billing_item_name',
            'billingItemQuantity' => 'billing_item_quantity',
            'billingItemRemark' => 'billing_item_remark',
            'billingItemAmount' => 'billing_item_amount',
            'billingItemTotal' => 'billing_item_total'
            */
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
