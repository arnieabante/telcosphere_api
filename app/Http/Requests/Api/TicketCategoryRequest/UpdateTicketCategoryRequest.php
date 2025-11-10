<?php

namespace App\Http\Requests\Api\TicketCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketCategoryRequest extends BaseTicketCategoryRequest
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
            'name' => ['sometimes', 'required', 'string', 'min:3', Rule::unique('roles')->ignore($this->uuid, 'uuid')],
            'isActive' => 'sometimes|required|boolean',
            'description' => 'nullable|string|min:3'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
