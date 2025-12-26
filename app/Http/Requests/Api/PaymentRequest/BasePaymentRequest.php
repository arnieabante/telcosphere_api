<?php

namespace App\Http\Requests\Api\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePaymentRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'receiptNo' => 'receipt_no',
            'clientId' => 'client_id',
            'collectionDate' => 'collection_date',
            'collectedBy' => 'collected_by',
            'paymentMethod' => 'payment_method',
            'paymentReference' => 'payment_reference',
            'subtotal' => 'subtotal',
            'discount' => 'discount',
            'total' => 'total',
            'amountReceived' => 'amount_received',
            'amountChanged' => 'amount_changed',
            'discount_reason' => 'discount_reason',
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
