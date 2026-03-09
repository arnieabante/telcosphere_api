<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceUserRequest extends BaseUserRequest
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
            'username' => ['required','string','min:3', Rule::unique('users')->where(fn ($query) => $query->where('site_id', $siteId))],
            'email' => ['required','string','min:3', Rule::unique('users')->where(fn ($query) => $query->where('site_id', $siteId))->ignore($this->uuid, 'uuid')],
            'password' => 'required|string|min:8',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
