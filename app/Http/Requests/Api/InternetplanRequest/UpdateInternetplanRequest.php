<?php

namespace App\Http\Requests\Api\InternetplanRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInternetplanRequest extends BaseInternetplanRequest
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
            'name' => ['sometimes','required','string','min:5', Rule::unique('internetplans')
                ->ignore($this->uuid, 'uuid')
                ->where(fn ($query) => $query
                    ->where('site_id', $siteId)
                    ->where('is_active', 1)
            )],
            'monthly_subscription' => 'sometimes|required|decimal:2'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
