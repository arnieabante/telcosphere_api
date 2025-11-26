<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ServerRequest\ReplaceServerRequest;
use App\Http\Requests\Api\ServerRequest\StoreServerRequest;
use App\Http\Requests\Api\ServerRequest\UpdateServerRequest;
use App\Http\Resources\Api\ServerResource;
use App\Models\Server;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ServerController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $include = $request->get('include');

        $query = Server::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $servers = $query->orderBy('name', 'asc')->get();
            return ServerResource::collection($servers);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        $servers = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return ServerResource::collection($servers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServerRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Server::class);

            return new ServerResource(
                Server::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Server.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $server = Server::where('uuid', $uuid)->firstOrFail();
            return new ServerResource($server);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Server does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Server.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServerRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Server::class);

            $server = Server::where('uuid', $uuid)->firstOrFail();
            $affected = $server->update($request->mappedAttributes());

            return new ServerResource($server);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Server does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Server.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceServerRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Server::class);

            $server = Server::where('uuid', $uuid)->firstOrFail();
            $affected = $server->update($request->mappedAttributes());

            return new ServerResource($server);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Server does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Server.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $server = Server::where('uuid', $uuid)->firstOrFail();
            $affected = $server->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Server does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Server.', 401);
        }
    }
}
