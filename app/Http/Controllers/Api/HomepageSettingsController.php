<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HomepageSettingsRequest\ReplaceHomepageSettingsRequest;
use App\Http\Requests\Api\HomepageSettingsRequest\StoreHomepageSettingsRequest;
use App\Http\Requests\Api\HomepageSettingsRequest\UpdateHomepageSettingsRequest;
use App\Http\Resources\Api\HomepageSettingsResource;
use App\Models\HomepageSettings; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class HomepageSettingsController extends ApiController
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

        $query = HomepageSettings::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $homepagesettings = $query->orderBy('name', 'asc')->get();
            return HomepageSettingsResource::collection($homepagesettings);
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
        
        $homepagesettings = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return HomepageSettingsResource::collection($homepagesettings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHomepageSettingsRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', HomepageSettings::class);

            return new HomepageSettingsResource(
                HomepageSettings::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a HomepageSettings.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $homepagesettings = HomepageSettings::where('uuid', $uuid)->firstOrFail();
            return new HomepageSettingsResource($homepagesettings);

        } catch (ModelNotFoundException $ex) {
            return $this->error('HomepageSettings does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a HomepageSettings.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHomepageSettingsRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', HomepageSettings::class);

            $homepagesettings = HomepageSettings::where('uuid', $uuid)->firstOrFail();

            // activate / deactivate
            if (isset($request['isActive'])) {
                $homepagesettings->update([
                    'is_active' => 0
                ]);
            } else 
                $affected = $homepagesettings->update($request->mappedAttributes());

            return new HomepageSettingsResource($homepagesettings);

        } catch (ModelNotFoundException $ex) {
            return $this->error('HomepageSettings does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a HomepageSettings.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceHomepageSettingsRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', HomepageSettings::class);

            $homepagesettings = HomepageSettings::where('uuid', $uuid)->firstOrFail();
            $affected = $homepagesettings->update($request->mappedAttributes());

            return new HomepageSettingsResource($homepagesettings);

        } catch (ModelNotFoundException $ex) {
            return $this->error('HomepageSettings does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a HomepageSettings.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $homepagesettings = HomepageSettings::where('uuid', $uuid)->firstOrFail();
            $affected = $homepagesettings->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('HomepageSettings does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a HomepageSettings.', 401);
        }
    }
}
