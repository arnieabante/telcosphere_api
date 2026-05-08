<?php

namespace App\Http\Requests\Api\SiteRequest;

use Illuminate\Validation\Rule;

class UpdateSiteRequest extends BaseSiteRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'companyName'             => [
                'sometimes','required','string','min:3', 
                Rule::unique('sites', 'company_name')->ignore($this->route('uuid'), 'uuid')
            ],
            'companyAddress'          => 'sometimes|required|string',
            // 'companyLogo'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'companyBanner'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'siteUrl'                 => 'nullable|string|max:255',
            'companyEmail'            => 'sometimes|required|email|string',
            'companyPhone'            => 'nullable|string',
            'companyTelephone'        => 'nullable|string',
            'invoiceIdPattern'        => 'nullable|string|max:8',
            'invoiceIdYYLastCount'    => 'sometimes|required|integer',
            'receiptIdPattern'        => 'nullable|string|max:8',
            'receiptIdYYLastCount'    => 'sometimes|required|integer',
            'paymentDetails'          => 'nullable|string',
            'isActive'                => 'sometimes|required|boolean',
        ];
    }
}