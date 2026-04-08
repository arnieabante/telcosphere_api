<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PesoWifiClientRequest\ReplacePesoWifiClientRequest;
use App\Http\Requests\Api\PesoWifiClientRequest\StorePesoWifiClientRequest;
use App\Http\Requests\Api\PesoWifiClientRequest\UpdatePesoWifiClientRequest;
use App\Http\Resources\Api\PesoWifiClientResource;
use App\Models\PesoWifiClient; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PesoWifiClientController extends ApiController
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
        $from = $request->get('from');
        $to = $request->get('to');

        $query = PesoWifiClient::with(['pesoWifiArea'])
            ->where('is_active', 1);

        if (!empty($include) && $include == 'all') {
            $pesowifiarea = $query->orderBy('name', 'asc')->get();
            return PesoWifiClientResource::collection($pesowifiarea);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('harvest_day', 'like', "%{$search}%");
                });
            }
        }

        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }
        
        $pesowifiarea = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return PesoWifiClientResource::collection($pesowifiarea);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePesoWifiClientRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', PesoWifiClient::class);

            return new PesoWifiClientResource(
                PesoWifiClient::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Peso Wifi Client.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $pesowifiarea = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            return new PesoWifiClientResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Peso Wifi Client.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePesoWifiClientRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', PesoWifiClient::class);

            $pesowifiarea = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->update($request->mappedAttributes());

            return new PesoWifiClientResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Peso Wifi Client.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplacePesoWifiClientRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', PesoWifiClient::class);

            $pesowifiarea = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->update($request->mappedAttributes());

            return new PesoWifiClientResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Peso Wifi Client.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $pesowifiarea = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Peso Wifi Client.', 401);
        }
    }
}
