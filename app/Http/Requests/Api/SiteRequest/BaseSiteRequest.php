<?php

namespace App\Http\Requests\Api\SiteRequest;

use Illuminate\Foundation\Http\FormRequest;

class BaseSiteRequest extends FormRequest
{
    public function mappedAttributes(): array {
        $attributes = [
            'companyName' => 'company_name',
            'companyAddress' => 'company_address',
            // 'companyLogo' => 'company_logo',
            'companyBanner' => 'company_banner',
            'siteUrl' => 'site_url',
            'companyEmail' => 'company_email',
            'companyPhone' => 'company_phone',
            'companyTelephone' => 'company_telephone',
            'invoiceIdPattern' => 'invoice_id_pattern',
            'invoiceIdYYLastCount' => 'invoice_id_yy_last_count',
            'receiptIdPattern' => 'receipt_id_pattern',
            'receiptIdYYLastCount' => 'receipt_id_yy_last_count',
            'accountNumberPattern' => 'account_number_pattern',
            'enableAccountNumberPattern' => 'enable_account_number_pattern',
            'accountNoLastCount' => 'account_no_last_count',
            'paymentDetails' => 'payment_details',
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
