<?php

namespace App\Http\Requests\Api\MikrotikRequest;

use Illuminate\Validation\Rule;

class StoreMikrotikRequest extends BaseMikrotikRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = auth()->user()->site_id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:100', Rule::unique('mikrotiks')->where(fn ($query) => $query->where('site_id', $siteId)->where('is_active', 1))],
            'serverId' => ['nullable', 'integer', Rule::exists('servers', 'id')->where('site_id', $siteId)],
            'ipAddress' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.:\-]+$/'],
            'port' => 'nullable|integer|between:1,65535',
            'useSsl' => 'nullable|boolean',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'timeout' => 'nullable|integer|between:1,30',
            'isActive' => 'sometimes|boolean',
        ];
    }
}
