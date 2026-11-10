<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteBankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'sitebankaccount',
            'attributes' => [
                'bankName' => $this->bank_name,
                'accountName' => $this->account_name,
                'accountNumber' => $this->account_number,
                'qrUrl' => $this->account_qr ? asset('storage/' . $this->account_qr) : null,
            ],
        ];
    }
}
