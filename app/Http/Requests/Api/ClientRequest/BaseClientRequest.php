<?php

namespace App\Http\Requests\Api\ClientRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseClientRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'firstName' => 'first_name',
            'middleName' => 'middle_name',
            'lastName' => 'last_name',
            'mobileNo' => 'mobile_no',
            'email' => 'email',
            'houseNo' => 'house_no',
            'accountNo' => 'account_no',
            'installationDate' => 'installation_date',
            'billingCategoryId' => 'billing_category_id',
            'status' => 'status',
            'inactiveDate' => 'inactive_date',
            'notes' => 'notes',
            'facebokkProfileUrl' => 'facebook_profile_url',
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
