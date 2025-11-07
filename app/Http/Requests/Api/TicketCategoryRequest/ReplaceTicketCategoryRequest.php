<?php

namespace App\Http\Requests\Api\TicketCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceTicketCategoryRequest extends BaseTicketCategoryRequest
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
            'name' => 'required|string|min:3|unique:ticketcategory',
            'description' => 'string|min:3',
            'isActive' => 'required|boolean'
        ];
    }
}
