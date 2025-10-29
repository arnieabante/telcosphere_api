<?php

namespace App\Http\Requests\Api\InternetplanRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceInternetplanRequest extends BaseInternetplanRequest
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
        return [
            'name' => 'required|string|min:5|unique:internetplans',
            'monthly_subscription' => 'required|decimal:2',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
