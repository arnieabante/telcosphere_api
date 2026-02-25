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
            'company_name'             => ['sometimes','required','string','min:3', Rule::unique('sites')->ignore($this->route('uuid'), 'uuid')],
            'company_logo'             => 'sometimes|nullable|image|max:2048',
            'company_banner'           => 'sometimes|nullable|image|max:4096',
            'site_url'                 => 'sometimes|required|string',
            'company_address'          => 'sometimes|required|string',
            'company_email'            => 'sometimes|required|email|string',
            'company_phone'            => 'nullable|string',
            'company_telephone'        => 'nullable|string',
            'invoice_id_pattern'       => 'nullable|string|max:8',
            'invoice_id_yy_last_count' => 'sometimes|required|integer',
            'receipt_id_pattern'       => 'nullable|string|max:8',
            'receipt_id_yy_last_count' => 'sometimes|required|integer',
            'payment_details'          => 'nullable|string',
            'is_active'                => 'sometimes|required|boolean',
        ];
    }
}