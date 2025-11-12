<?php

namespace App\Http\Requests\Api\EmployeeRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseEmployeeRequest extends FormRequest
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
            'inactiveDate' => 'inactive_date',
            'notes' => 'notes',
            'facebookProfileUrl' => 'facebook_profile_url',
            'billingCategoryId' => 'billing_category_id',
            'serverId' => 'server_id',
            'internetPlanId' => 'internet_plan_id',
            'isActive' => 'is_active'
        ];
        // TODO: Need to update the data once UI is already finished
        $attributesToUpdate = [];

        foreach ($attributes as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
