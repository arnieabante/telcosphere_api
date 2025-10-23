<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseUserRequest
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
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($this->uuid, 'uuid')],
            'username' => ['sometimes', 'required', 'string', Rule::unique('users')->ignore($this->uuid, 'uuid')],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'isActive' => ['sometimes', 'required', 'boolean'],
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
