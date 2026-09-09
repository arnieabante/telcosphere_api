<?php

namespace App\Http\Requests\Api\TicketCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCategoryRequest extends BaseTicketCategoryRequest
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
            'name' => ['required','string','min:5', Rule::unique('ticket_categories')->where(fn ($query) => $query->where('site_id', $siteId))],
            'description' => 'string|min:3',
            'isActive' => 'required|boolean'
        ];
    }
}
