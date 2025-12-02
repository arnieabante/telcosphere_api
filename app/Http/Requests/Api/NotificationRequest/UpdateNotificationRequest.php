<?php

namespace App\Http\Requests\Api\NotificationRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends BaseNotificationRequest
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
            'userId' => 'int|null',
            'ticketId' => 'int|null',
            'type' => 'string|null|max:255',
            'message' => 'string|null|max:255',
            'isRead' => 'boolean',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
