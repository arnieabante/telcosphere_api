<?php

namespace App\Http\Requests\Api\SiteBankAccountRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseSiteBankAccountRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'bankName' => 'bank_name',
            'accountName' => 'account_name',
            'accountNumber' => 'account_number',
            'accountQR' => 'account_qr',
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
