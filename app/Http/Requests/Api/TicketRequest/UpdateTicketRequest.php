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
            'ticketType' => 'sometimes|string',
            'clientId' => 'nullable|string',
            'requestorName' => 'string|max:50',
            'requestorLocation' => 'nullable|max:255',
            'name' => 'sometimes|string|max:50',
            'description' => 'nullable|string|max:100',
            'categoryId' => 'sometimes|string',
            'requestedDate' => 'sometimes|string',
            'dueDate' => 'nullable|string',
            'assignedTo' => 'nullable|string',
            'status' => 'sometimes|string',
            'remarks' => 'nullable|string',
            'isActive' => 'sometimes|required|string'
        ];
    }
}
