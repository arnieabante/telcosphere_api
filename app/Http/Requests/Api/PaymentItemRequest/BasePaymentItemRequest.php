<?php

namespace App\Http\Requests\Api\PaymentItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePaymentItemRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'billingId' => 'billing_id',
            'billingItemId' => 'billing_item_id',
            'particulars' => 'particulars',
            'amount' => 'amount',
            'amountPaid' => 'amount_paid',
            'amountBalance' => 'amount_balance',
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
