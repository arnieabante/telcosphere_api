<?php

namespace App\Http\Requests\Api\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;

class BasePaymentRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'clientId' => 'client_id',
            'receiptNo' => 'receipt_no',
            'paymentDate' => 'payment_date',
            'paymentName' => 'payment_name',
            'paymentAmount' => 'payment_amount',
            'reference' => 'reference',
            'discount' => 'discount',
            'discount_total' => 'discount_total',
            'discount_reason' => 'discount_reason',
            'payment_date' => 'payment_date',
            'collected_by' => 'collected_by',
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
