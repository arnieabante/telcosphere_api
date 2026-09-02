<?php

namespace App\Http\Requests\Api\SiteRequest;

use Illuminate\Validation\Rule;

class ReplaceSiteRequest extends BaseSiteRequest
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
            // 'companyLogo'             => 'sometimes|nullable|image|max:2048',
            'companyBanner'           => 'sometimes|nullable|image|max:4096',
            'siteUrl'                 => 'sometimes|required|string',
            'companyAddress'          => 'sometimes|required|string',
            'companyEmail'            => 'sometimes|required|email|string',
            'companyPhone'            => 'nullable|string',
            'companyTelephone'        => 'nullable|string',
            'invoiceIdPattern'       => 'nullable|string|max:8',
            'invoiceIdYYLastCount' => 'sometimes|required|integer',
            'receiptIdPattern'       => 'nullable|string|max:8',
            'receiptIdYYLastCount' => 'sometimes|required|integer',
            'accountNumberPattern' => 'nullable|string|max:20',
            'enableAccountNumberPattern' => 'nullable|boolean',
            'accountNoLastCount' => 'sometimes|integer|min:0',
            'paymentDetails'          => 'nullable|string',
            'isActive'                => 'sometimes|required|boolean',
        ];
    }
}