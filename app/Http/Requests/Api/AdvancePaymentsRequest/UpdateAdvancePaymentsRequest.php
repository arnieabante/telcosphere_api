<?php

namespace App\Http\Requests\Api\AdvancePaymentsRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvancePaymentsRequest extends BaseAdvancePaymentsRequest
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
            'clientId' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('site_id', $siteId)->where('is_active', 1)),
                Rule::unique('advance_payments', 'client_id')->ignore($this->uuid, 'uuid')
            ],
            'amount' => 'sometimes|required|numeric|decimal:0,2|min:0|max:999999.99'
        ];
    }
}
