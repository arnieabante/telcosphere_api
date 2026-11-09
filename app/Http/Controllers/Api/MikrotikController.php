<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\MikrotikRequest\ReplaceMikrotikRequest;
use App\Http\Requests\Api\MikrotikRequest\StoreMikrotikRequest;
use App\Http\Requests\Api\MikrotikRequest\UpdateMikrotikRequest;
use App\Http\Resources\Api\MikrotikResource;
use App\Models\Client;
use App\Models\Mikrotik;
use App\Services\Mikrotik\RouterClientFactory;
use App\Services\Mikrotik\RouterException;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class MikrotikController extends ApiController
{
    use ApiResponses;

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Mikrotik::where('is_active', 1);

        if ($request->get('include') === 'all') {
            return MikrotikResource::collection($query->orderBy('name', 'asc')->get());
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return MikrotikResource::collection($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(StoreMikrotikRequest $request)
    {
        return new MikrotikResource(Mikrotik::create([
            ...$request->mappedAttributes(),
            'site_id' => auth()->user()->site_id,
        ]));
    }

    public function show(string $uuid)
    {
        try {
            return new MikrotikResource(Mikrotik::where('uuid', $uuid)->firstOrFail());
        } catch (ModelNotFoundException $ex) {
            return $this->error('MikroTik router does not exist.', 404);
        }
    }

    public function update(UpdateMikrotikRequest $request, string $uuid)
    {
        return $this->save($request->mappedAttributes(), $uuid);
    }

    public function replace(ReplaceMikrotikRequest $request, string $uuid)
    {
        return $this->save($request->mappedAttributes(), $uuid);
    }

    public function destroy(string $uuid)
    {
        try {
            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();

            if ($this->hasClients($mikrotik)) {
                return $this->error('Move this router\'s clients to another router before deleting it.', 409);
            }

            return $this->ok('Deleted '.$mikrotik->delete().' record.', []);
        } catch (ModelNotFoundException $ex) {
            return $this->error('MikroTik router does not exist.', 404);
        }
    }

    /**
     * Try a login without saving anything. A saved router's password is reused
     * when the form leaves the password blank.
     */
    public function testConnection(Request $request, RouterClientFactory $factory)
    {
        $data = $request->validate([
            'uuid' => 'nullable|string',
            'ipAddress' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.:\-]+$/'],
            'port' => 'nullable|integer|between:1,65535',
            'useSsl' => 'nullable|boolean',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string|max:255',
            'timeout' => 'nullable|integer|between:1,30',
        ]);

        $password = $data['password'] ?? null;

        if (blank($password) && !empty($data['uuid'])) {
            $password = Mikrotik::where('uuid', $data['uuid'])->first()?->password;
        }

        if (blank($password)) {
            return $this->error('Enter the router password to test the connection.', 422);
        }

        $useSsl = (bool) ($data['useSsl'] ?? false);

        try {
            $api = $factory->connect(
                $data['ipAddress'],
                (int) ($data['port'] ?? ($useSsl ? 8729 : 8728)),
                $useSsl,
                $data['username'],
                $password,
                (int) ($data['timeout'] ?? 5)
            );
            $info = $api->info();
            $api->close();
        } catch (RouterException $ex) {
            return $this->error($ex->getMessage(), 422);
        }

        return $this->ok("Connected to {$info['identity']} (RouterOS {$info['version']}).", $info);
    }

    private function save(array $attributes, string $uuid)
    {
        try {
            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();

            if (array_key_exists('is_active', $attributes) && !$attributes['is_active'] && $this->hasClients($mikrotik)) {
                return $this->error('Move this router\'s clients to another router before deleting it.', 409);
            }

            $mikrotik->update($attributes);

            return new MikrotikResource($mikrotik);
        } catch (ModelNotFoundException $ex) {
            return $this->error('MikroTik router does not exist.', 404);
        }
    }

    private function hasClients(Mikrotik $mikrotik): bool
    {
        return Client::where('mikrotik_id', $mikrotik->id)->where('is_active', 1)->exists();
    }
}
