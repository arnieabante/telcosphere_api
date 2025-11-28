<?php

namespace App\Http\Requests\Api\TicketRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends BaseTicketRequest
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
            'clientId' => 'sometimes|required|string',
            'name' => 'sometimes|required|string|max:50',
            'description' => 'nullable|string|max:100',
            'categoryId' => 'sometimes|required|string',
            'requestedDate' => 'sometimes|required|string',
            'dueDate' => 'nullable|string',
            'assignedTo' => 'nullable|string', 
            'status' => 'sometimes|required|string',
            'remarks' => 'nullable|string',
            'isActive' => 'sometimes|required|string'
        ];
    }
}
