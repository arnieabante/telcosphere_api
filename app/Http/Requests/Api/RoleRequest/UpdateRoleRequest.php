<?php

namespace App\Http\Requests\Api\RoleRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends BaseRoleRequest
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
            'name' => 'sometimes|required|string|min:3|unique:roles',
            'description' => 'sometimes|required|string|max:100',
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
