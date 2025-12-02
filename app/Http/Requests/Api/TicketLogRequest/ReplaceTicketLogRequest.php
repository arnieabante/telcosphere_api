<?php

namespace App\Http\Requests\Api\TicketLogRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceTicketLogRequest extends BaseTicketLogRequest
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
            'ticketId' => 'int|null',
            'userId' => 'int|null',
            'action' => 'string|null|max:255',
            'oldValue' => 'string|null|max:255',
            'newValue' => 'string|null|max:255',
            'note' => 'string|null|max:255',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
