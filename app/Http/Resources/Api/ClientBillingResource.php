<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientBillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'billing',
            'attributes' => [
                'uuid' => $this->uuid,
                'invoiceNumber' => $this->invoice_number,
                'billingDate' => $this->billing_date,
                'billingDescription' => $this->billing_description,
                'amountDue' => number_format($this->billing_total - $this->advance_payment, 2, '.', ''),
                'billingBalance' => $this->billing_balance,
                'billingStatus' => $this->billing_status,
            ],
        ];
    }
}
