<?php

namespace App\Http\Requests\Api\ExpensesRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceExpensesRequest extends BaseExpensesRequest
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
            'expenseDate' => 'sometimes|required|date',
            'staffName' => 'sometimes|required|string|min:3',
            'expenseTotal' => 'sometimes|required|decimal:0,2',
            'isActive' => 'sometimes|required|boolean'
        ];
    }
}
