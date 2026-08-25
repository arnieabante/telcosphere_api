<?php

namespace App\Http\Requests\Api\UserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\Role;

class StoreUserRequest extends BaseUserRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $siteId = auth()->user()->site_id
            ?? session('site_id')
            ?? request()->header('site_id')
            ?? 1;

        return [
            'fullname' => 'required|string|min:2',

            'username' => [
                'required',
                'string',
                'min:3',
                Rule::unique('users')
                    ->where(fn ($query) => $query->where('site_id', $siteId))
            ],

            'email' => [
                'required',
                'string',
                'min:3',
                Rule::unique('users')
                    ->where(fn ($query) => $query->where('site_id', $siteId))
            ],

            'password' => 'required|string|min:8',

            // Optional
            'roleId' => 'nullable|string',
        ];
    }

    public function mappedAttributes(): array
    {
        $attributes = parent::mappedAttributes();

        // If roleId was NOT supplied,
        // this request is assumed to be creating a Client.
        if (!$this->filled('roleId')) {

            $clientRole = Role::firstOrCreate(
                ['name' => 'Client'],
                [
                    'is_active' => 1
                ]
            );

            $attributes['role_id'] = $clientRole->id;
        }

        return $attributes;
    }
}
