<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends BaseUserRequest
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
            'fullname' => 'required|string|min:2',
            'username' => 'required|string|min:2',
            'email' => ['required','string','min:5', Rule::unique('users')->where(fn ($query) => $query->where('site_id', $siteId))],
            'password' => 'required|string|min:8',
            'roleId' => 'required|string'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
