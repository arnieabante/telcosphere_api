<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MikrotikRequest\ReplaceMikrotikRequest;
use App\Http\Requests\Api\MikrotikRequest\StoreMikrotikRequest;
use App\Http\Requests\Api\MikrotikRequest\UpdateMikrotikRequest;
use App\Http\Resources\Api\MikrotikResource;
use App\Models\Mikrotik; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MikrotikController extends ApiController
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
        $forHarvest = $request->get('for_harvest'); 

        $query = Mikrotik::where('is_active', 1);

        // FOR HARVEST FILTER
        if (!empty($forHarvest) && $forHarvest == 1) {
            $query->where(function ($q) {
                $q->where('next_harvest_date', '<=', now()->addDays(5))
                ->orWhereNull('last_harvest_date');
            });
        }

        if (!empty($include) && $include == 'all') {
            $mikrotik = $query->orderBy('name', 'asc')->get();
            return MikrotikResource::collection($mikrotik);
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
        
        $mikrotik = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return MikrotikResource::collection($mikrotik);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMikrotikRequest $request)
    {
        try {
            $data = $request->mappedAttributes();
            return new MikrotikResource(
                Mikrotik::create($data)
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
            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();
            return new MikrotikResource($mikrotik);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Peso Wifi Client.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMikrotikRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Mikrotik::class);

            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();
            $affected = $mikrotik->update($request->mappedAttributes());

            return new MikrotikResource($mikrotik);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Peso Wifi Client.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceMikrotikRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Mikrotik::class);

            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();
            $affected = $mikrotik->update($request->mappedAttributes());

            return new MikrotikResource($mikrotik);

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
            $mikrotik = Mikrotik::where('uuid', $uuid)->firstOrFail();
            $affected = $mikrotik->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Peso Wifi Client.', 401);
        }
    }
}