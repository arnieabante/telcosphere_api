<?php

namespace App\Http\Requests\Api\BillingCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseBillingCategoryRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'name' => 'name',
            'description' => 'description',
            'dateCycle' => 'date_cycle',
            'daysToDueDate' => 'days_to_due_date',
            'daysToDisconnectionDate' => 'days_to_disconnection_date',
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
