<?php

namespace App\Http\Requests\Api\SiteRequest;

class StoreSiteRequest extends BaseSiteRequest
{
    public function rules(): array
    {
        return [
            'companyName'             => 'required|string|min:3',
            'siteUrl'                 => 'required|string|max:255',
            'companyAddress'          => 'required|string|max:255',
            'companyEmail'            => 'required|email|max:255',
            'invoiceIdYYLastCount' => 'required|integer',
            'receiptIdYYLastCount' => 'required|integer',
            'isActive'                => 'required|boolean',
            'companyLogo'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'companyBanner'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'companyPhone'            => 'nullable|string|max:50',
            'companyTelephone'        => 'nullable|string|max:50',
            'invoiceIdPattern'       => 'nullable|string|max:8',
            'receiptIdPattern'       => 'nullable|string|max:8',
            'paymentDetails'          => 'nullable|string',
        ];
    }
}