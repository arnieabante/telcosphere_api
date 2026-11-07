<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveSiteBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bankName' => 'required|string|max:255',
            'accountName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:255',
            // no svg, it can carry scripts and is served from the API's own domain
            'accountQR' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ];
    }
}
