<?php

namespace App\Http\Requests\Api\ClientRequest;

use App\Models\Client;
use App\Models\Mikrotik;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesNetworkFields
{
    protected function networkRules(): array
    {
        $siteId = auth()->user()->site_id;
        $uuid = $this->route('uuid');
        $existing = $uuid ? Client::where('uuid', $uuid)->first(['id', 'server_id', 'mikrotik_id']) : null;
        $routerId = $this->has('mikrotikId') ? $this->input('mikrotikId') : $existing?->mikrotik_id;
        $serverId = $this->has('serverId') ? $this->input('serverId') : $existing?->server_id;

        $username = ['sometimes', 'nullable', 'string', 'max:64', 'regex:/^[^\s[:cntrl:]]+$/'];

        // a secret name is unique per router, so two clients on one router cannot share it
        if ($routerId) {
            $username[] = Rule::unique('clients', 'pppoe_username')
                ->where(fn ($query) => $query->where('site_id', $siteId)->where('mikrotik_id', $routerId)->where('is_active', 1))
                ->ignore($existing?->id);
        }

        return [
            'PPPOEUsername' => $username,
            'PPPOEPassword' => ['sometimes', 'nullable', 'string', 'max:128', 'regex:/^[^[:cntrl:]]+$/'],
            'suspensionExemptUntil' => ['sometimes', 'nullable', 'date'],
            'mikrotikId' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('mikrotiks', 'id')->where(fn ($query) => $query->where('site_id', $siteId)->where('is_active', 1)),
                function (string $attribute, mixed $value, Closure $fail) use ($serverId) {
                    $routerServer = Mikrotik::whereKey($value)->value('server_id');

                    if ($routerServer && $serverId && (int) $routerServer !== (int) $serverId) {
                        $fail("The selected router does not serve this client's server area.");
                    }
                },
            ],
        ];
    }
}
