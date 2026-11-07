<?php

namespace App\Http\Requests\Api\ClientsDashboardRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'categoryId' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')
                    ->where('site_id', $this->user()->site_id)
                    ->where('is_active', 1),
            ],
            'remarks' => 'nullable|string|max:255',
        ];
    }
}
