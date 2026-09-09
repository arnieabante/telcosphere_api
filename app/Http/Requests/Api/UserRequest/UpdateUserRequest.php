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
        $siteId = auth()->user()->site_id ?? session('site_id') ?? request()->header('site_id')?? 1;
        return [
            'fullname' => 'sometimes|required|string|min:2',
            'username' => ['sometimes', 'required', 'string', Rule::unique('users')->ignore($this->uuid, 'uuid')],
            'email' => ['sometimes','required','string','min:3', Rule::unique('users')->where(fn ($query) => $query->where('site_id', $siteId))->ignore($this->uuid, 'uuid')],
            'password' => 'sometimes|required|string|min:8',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
