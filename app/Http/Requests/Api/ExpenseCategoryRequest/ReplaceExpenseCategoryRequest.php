<?php

namespace App\Http\Requests\Api\ExpenseCategoryRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceExpenseCategoryRequest extends BaseExpenseCategoryRequest
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
            'name' => ['sometimes','required','string','min:3', Rule::unique('expense_categories')->where(fn ($query) => $query->where('site_id', $siteId))->ignore($this->uuid, 'uuid')],
            'description' => 'nullable|string|min:5',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
