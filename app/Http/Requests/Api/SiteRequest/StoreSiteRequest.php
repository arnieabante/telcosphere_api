<?php

namespace App\Http\Requests\Api\SiteRequest;

class StoreSiteRequest extends BaseSiteRequest
{
    public function rules(): array
    {
        return [
            'company_name'             => 'required|string|min:3',
            'site_url'                 => 'required|string|max:255',
            'company_address'          => 'required|string|max:255',
            'company_email'            => 'required|email|max:255',
            'invoice_id_yy_last_count' => 'required|integer',
            'receipt_id_yy_last_count' => 'required|integer',
            'is_active'                => 'required|boolean',
            'company_logo'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'company_banner'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'company_phone'            => 'nullable|string|max:50',
            'company_telephone'        => 'nullable|string|max:50',
            'invoice_id_pattern'       => 'nullable|string|max:8',
            'receipt_id_pattern'       => 'nullable|string|max:8',
            'payment_details'          => 'nullable|string',
        ];
    }
}