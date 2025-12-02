<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientRequest\ReplaceClientRequest;
use App\Http\Requests\Api\ClientRequest\StoreClientRequest;
use App\Http\Requests\Api\ClientRequest\UpdateClientRequest;
use App\Http\Resources\Api\ClientResource;
use App\Models\Client;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ClientController extends ApiController
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

        $query = Client::with(['internetPlan', 'billingCategory', 'server'])
            ->where('is_active', 1);

        if (!empty($include) && $include == 'all') {
           $clients = $query->orderBy('first_name', 'asc')->get();
           return ClientResource::collection($clients);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]) 
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('installation_date', 'like', "%{$search}%")
                    ->orWhere('house_no', 'like', "%{$search}%")
                    ->orWhereHas('internetPlan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%"); 
                    })
                    ->orWhereHas('server', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%"); 
                    })
                    ->orWhereHas('billingCategory', function ($billingQuery) use ($search) {
                        $billingQuery->where('name', 'like', "%{$search}%"); 
                    });
                });
            }
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ClientResource::collection($clients);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Client::class);

            return new ClientResource(
                Client::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Client.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $client = Client::with(['internetPlan', 'billingCategory', 'server'])->where('uuid', $uuid)->firstOrFail();
            return new ClientResource($client);
            

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Client.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Client::class);

            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->update($request->mappedAttributes());

            return new ClientResource($client);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Client.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceClientRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Client::class);

            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->update($request->mappedAttributes());

            return new ClientResource($client);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Client.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Client.', 401);
        }
    }
}
