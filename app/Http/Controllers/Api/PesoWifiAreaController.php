<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PesoWifiAreaRequest\ReplacePesoWifiAreaRequest;
use App\Http\Requests\Api\PesoWifiAreaRequest\StorePesoWifiAreaRequest;
use App\Http\Requests\Api\PesoWifiAreaRequest\UpdatePesoWifiAreaRequest;
use App\Http\Resources\Api\PesoWifiAreaResource;
use App\Models\PesoWifiArea; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PesoWifiAreaController extends ApiController
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

        $query = PesoWifiArea::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $pesowifiarea = $query->orderBy('name', 'asc')->get();
            return PesoWifiAreaResource::collection($pesowifiarea);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('monthly_subscription', 'like', "%{$search}%");
                });
            }
        }

        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }
        
        $pesowifiarea = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return PesoWifiAreaResource::collection($pesowifiarea);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePesoWifiAreaRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', PesoWifiArea::class);

            return new PesoWifiAreaResource(
                PesoWifiArea::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a PesoWifiArea.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $pesowifiarea = PesoWifiArea::where('uuid', $uuid)->firstOrFail();
            return new PesoWifiAreaResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiArea does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a PesoWifiArea.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePesoWifiAreaRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', PesoWifiArea::class);

            $pesowifiarea = PesoWifiArea::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->update($request->mappedAttributes());

            return new PesoWifiAreaResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiArea does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a PesoWifiArea.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplacePesoWifiAreaRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', PesoWifiArea::class);

            $pesowifiarea = PesoWifiArea::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->update($request->mappedAttributes());

            return new PesoWifiAreaResource($pesowifiarea);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiArea does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a PesoWifiArea.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $pesowifiarea = PesoWifiArea::where('uuid', $uuid)->firstOrFail();
            $affected = $pesowifiarea->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('PesoWifiArea does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a PesoWifiArea.', 401);
        }
    }
}
