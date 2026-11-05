<?php

namespace App\Http\Requests\Api\AdvancePaymentsRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvancePaymentsRequest extends BaseAdvancePaymentsRequest
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
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('site_id', $siteId)->where('is_active', 1))
            ],
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:999999.99',
            // when given, the deposit is also recorded as a collection
            'paymentMethod' => 'nullable|string|max:50',
            'reference' => 'nullable|string',
            'collectionDate' => 'nullable|date',
            'collectedBy' => 'nullable|string'
        ];
    }
}
