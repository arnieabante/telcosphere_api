<?php

namespace App\Http\Requests\Api\AdvancePaymentsRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceAdvancePaymentsRequest extends BaseAdvancePaymentsRequest
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
            'clientId' => ['sometimes|required'],
            'amount' => 'nullable|max:100'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
