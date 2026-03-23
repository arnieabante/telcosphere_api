<?php

namespace App\Http\Requests\Api\BillingCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingCategoryRequest extends BaseBillingCategoryRequest
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
           'name' => ['string','min:3', Rule::unique('billing_categories')->where(fn ($query) => $query->where('site_id', $siteId))->ignore($this->uuid, 'uuid')],
            'description' => 'sometimes|string|max:100',
            'dateCycle' => 'integer',
            'daysToDueDate' => 'integer',
            'daysToDisconnectionDate' => 'integer',
            'isActive' => 'sometimes|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
