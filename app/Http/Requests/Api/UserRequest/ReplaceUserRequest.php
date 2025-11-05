<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'fullname' => 'required|string|min:5',
            'username' => 'required|string|min:5|unique:users',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
