<?php

namespace App\Http\Requests\Api\SiteBankAccountRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteBankAccountRequest extends BaseSiteBankAccountRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = auth()->user()->site_id ?? session('site_id') ?? request()->header('site_id')?? 1;
        return [
            'bankName' => 'required|string|min:3',
            'accountName' =>'required|string|min:3',
            'accountNumber' =>'required|string|min:3',
            'accountQR' => 'sometimes|required|file'
        ];
    }
}
