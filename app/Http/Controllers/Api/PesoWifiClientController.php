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
use Carbon\Carbon;

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
        $forHarvest = $request->get('for_harvest'); 

        $query = PesoWifiClient::with(['pesoWifiArea'])
            ->where('is_active', 1);

        // FOR HARVEST FILTER
        if (!empty($forHarvest) && $forHarvest == 1) {
            $query->where(function ($q) {
                $q->where('next_harvest_date', '<=', now()->addDays(5))
                ->orWhereNull('last_harvest_date');
            });
        }

        if (!empty($include) && $include == 'all') {
            $pesowificlient = $query->orderBy('name', 'asc')->get();
            return PesoWifiClientResource::collection($pesowificlient);
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
        
        $pesowificlient = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return PesoWifiClientResource::collection($pesowificlient);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePesoWifiClientRequest $request)
    {
        try {
            $data = $request->mappedAttributes();

            $harvestDay = (int) ($data['harvest_day'] ?? 0);

            $nextHarvestDate = now()
                ->addMonthNoOverflow();

            $nextHarvestDate = $nextHarvestDate->setDay(
                min($harvestDay, $nextHarvestDate->daysInMonth)
            );

            $data['next_harvest_date'] = $nextHarvestDate;
            $data['last_harvest_date'] = $data['last_harvest_date'] ?? null;
            $data['is_harvested'] = false;

            return new PesoWifiClientResource(
                PesoWifiClient::create($data)
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
            $pesowificlient = PesoWifiClient::with(['pesoWifiArea'])->where('uuid', $uuid)->firstOrFail();
            return new PesoWifiClientResource($pesowificlient);

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

            $pesowificlient = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowificlient->update($request->mappedAttributes());

            return new PesoWifiClientResource($pesowificlient);

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

            $pesowificlient = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowificlient->update($request->mappedAttributes());

            return new PesoWifiClientResource($pesowificlient);

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
            $pesowificlient = PesoWifiClient::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowificlient->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Peso Wifi Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Peso Wifi Client.', 401);
        }
    }
}