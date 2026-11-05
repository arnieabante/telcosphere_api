<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PesoWifiHarvestRequest\ReplacePesoWifiHarvestRequest;
use App\Http\Requests\Api\PesoWifiHarvestRequest\StorePesoWifiHarvestRequest;
use App\Http\Requests\Api\PesoWifiHarvestRequest\UpdatePesoWifiHarvestRequest;
use App\Http\Resources\Api\PesoWifiHarvestResource;
use App\Models\PesoWifiHarvest;
use App\Models\PesoWifiClient;
use App\Services\DashboardService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Services\PesoWifiDashboardService;
use Carbon\Carbon;

class PesoWifiHarvestController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $from = $request->get('from');
        $to = $request->get('to');
        $include = $request->get('include');

        $query = PesoWifiHarvest::with(['pesoWifiClient.pesoWifiArea', 'collectedBy'])
            ->where('is_active', 1);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {

                //  SEARCH IN HARVEST TABLE
                $q->where('remarks', 'like', "%{$search}%")
                ->orWhere('amount_harvested', 'like', "%{$search}%")
                ->orWhere('resellers_income', 'like', "%{$search}%")
                ->orWhere('owner_income', 'like', "%{$search}%")
                ->orWhere('total_deductions', 'like', "%{$search}%");

                //  SEARCH IN CLIENT NAME (RELATIONSHIP)
                $q->orWhereHas('pesoWifiClient', function ($client) use ($search) {
                    $client->where('name', 'like', "%{$search}%");
                });

                //  SEARCH IN AREA NAME (nested relationship)
                $q->orWhereHas('pesoWifiClient.pesoWifiArea', function ($area) use ($search) {
                    $area->where('name', 'like', "%{$search}%");
                });

                //  SEARCH IN USER (Collected By)
                $q->orWhereHas('collectedBy', function ($user) use ($search) {
                    $user->where('fullname', 'like', "%{$search}%");
                });
            });
        }

        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $totalPesoWifiHarvest = PesoWifiHarvest::where('is_active', 1)->count();

        // Return ALL records for reporting
        if ($include === 'all') {
            $pesowifiharvests = $query
                ->orderBy('created_at', 'desc')
                ->get();

            return PesoWifiHarvestResource::collection($pesowifiharvests)
                ->additional([
                    'meta' => [
                        'pesowifiharvests_total' => $totalPesoWifiHarvest
                    ]
                ]);
        }

        $pesowifiharvests = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return PesoWifiHarvestResource::collection($pesowifiharvests)
            ->additional([
                'meta' => [
                    'pesowifiharvests_total' => $totalPesoWifiHarvest
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePesoWifiHarvestRequest $request)
    {
        try {

            $data = $request->mappedAttributes();

            $clientId = $request->input('pesoWifiClientId'); // ✅ FIX HERE

            // create harvest
            $harvest = PesoWifiHarvest::create($data);

            // get client
            $client = PesoWifiClient::find($clientId);

            if ($client) {

                $today = Carbon::now();

                // last harvest date = today
                $client->last_harvest_date = $today->toDateString();

                $harvestDay = (int) $client->harvestDay;

                $nextMonth = $today->copy()->addMonth();

                $nextHarvestDate = Carbon::create(
                    $nextMonth->year,
                    $nextMonth->month,
                    $harvestDay
                );

                $client->next_harvest_date = $nextHarvestDate->toDateString();

                $client->save();
            }

            return new PesoWifiHarvestResource($harvest);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a PesoWifiHarvest.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $pesowifiharvest = PesoWifiHarvest::with(['pesoWifiClient.pesoWifiArea'])->where('uuid', $uuid)->firstOrFail();
            return new PesoWifiHarvestResource($pesowifiharvest);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiHarvest does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a PesoWifiHarvest.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePesoWifiHarvestRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', PesoWifiHarvest::class);

            $pesowifiharvest = PesoWifiHarvest::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiharvest->update($request->mappedAttributes());

            return new PesoWifiHarvestResource($pesowifiharvest);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiHarvest does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a PesoWifiHarvest.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplacePesoWifiHarvestRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', PesoWifiHarvest::class);

            $pesowifiharvest = PesoWifiHarvest::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiharvest->update($request->mappedAttributes());

            return new PesoWifiHarvestResource($pesowifiharvest);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiHarvest does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a PesoWifiHarvest.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $pesowifiharvest = PesoWifiHarvest::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiharvest->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiHarvest does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a PesoWifiHarvest.', 401);
        }
    }

    public function dashboard(PesoWifiDashboardService $service)
    {
        return response()->json([
            'total_areas' => $service->getTotalAreas(),
            'total_resellers' => $service->getTotalResellers(),
            'monthly_collections' => $service->getMonthlyCollections(),
            'total_collections' => $service->getTotalCollections(),
            'clients_status' => $service->getClientsStatus(),
            'monthly_sales' => $service->getMonthlySales(),
        ]);
    }
}
