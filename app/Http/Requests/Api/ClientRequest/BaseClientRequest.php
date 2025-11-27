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
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'accountNo' => 'account_no',
            'installationDate' => 'installation_date',
            'installationFee' => 'installation_fee',
            'balanceFromPrevBilling' => 'balance_from_prev_billing',
            'prorateFee' => 'prorate_fee',
            'prorateStartDate' => 'prorate_start_date',
            'prorateEndDate' => 'prorate_end_date',
            'prorateFeeRemarks' => 'prorate_fee_remarks',
            'prorateFeeStatus' => 'prorate_fee_status',
            'inactiveDate' => 'inactive_date',
            'notes' => 'notes',
            'facebookProfileUrl' => 'facebook_profile_url',
            'billingCategoryId' => 'billing_category_id',
            'serverId' => 'server_id',
            'internetPlanId' => 'internet_plan_id',
            'lastAutoBillingDate' => 'last_auto_billing_date',
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
