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

            // fields not from model
            'billingType' => 'billing_type',
            'billingCategory' => 'billing_category'
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
