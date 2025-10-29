<?php

namespace App\Http\Requests\Api\ModuleRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuleRequest extends BaseModuleRequest
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
            // 'name' => 'sometimes|required|string|min:5|unique:modules',
            'name' => ['sometimes', 'required', 'string', 'min:5', Rule::unique('modules')->ignore($this->uuid, 'uuid')],
            'description' => 'sometimes|required|string|max:100',
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
