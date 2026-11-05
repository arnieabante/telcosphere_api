<?php

namespace App\Http\Requests\Api\AdvancePaymentsRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseAdvancePaymentsRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'clientId' => 'client_id',
            'amount' => 'amount'
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
