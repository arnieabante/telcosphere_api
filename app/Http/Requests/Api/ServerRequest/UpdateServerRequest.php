<?php

namespace App\Http\Requests\Api\ServerRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends BaseServerRequest
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
            'name' => ['required','string','min:3', Rule::unique('servers')->where(fn ($query) => $query->where('site_id', $siteId))],
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
