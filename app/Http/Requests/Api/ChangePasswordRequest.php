<?php

namespace App\Http\Requests\Api;

use App\Services\ClientUserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string', 'current_password:sanctum'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:currentPassword',
                Rule::notIn([ClientUserService::DEFAULT_PASSWORD]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => 'The new password must be different from your current password.',
            'password.not_in' => 'Please choose a password other than the default one.',
        ];
    }
}
