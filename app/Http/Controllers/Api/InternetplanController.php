<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InternetplanRequest\ReplaceInternetplanRequest;
use App\Http\Requests\Api\InternetplanRequest\StoreInternetplanRequest;
use App\Http\Requests\Api\InternetplanRequest\UpdateInternetplanRequest;
use App\Http\Resources\Api\InternetplanResource;
use App\Models\Internetplan; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class InternetplanController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Internetplan::query()
            ->where('is_active', 1);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('monthly_subscription', 'like', "%{$search}%");
            });
        }

        $internetplan = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return InternetplanResource::collection($internetplan);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInternetplanRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Internetplan::class);

            return new InternetplanResource(
                Internetplan::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Internetplan.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $internetplan = Internetplan::where('uuid', $uuid)->firstOrFail();
            return new InternetplanResource($internetplan);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Internetplan does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Internetplan.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInternetplanRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Internetplan::class);

            $internetplan = Internetplan::where('uuid', $uuid)->firstOrFail();
            $affected = $internetplan->update($request->mappedAttributes());

            return new InternetplanResource($internetplan);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Internetplan does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Internetplan.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceInternetplanRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Internetplan::class);

            $internetplan = Internetplan::where('uuid', $uuid)->firstOrFail();
            $affected = $internetplan->update($request->mappedAttributes());

            return new InternetplanResource($internetplan);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Internetplan does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Internetplan.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $internetplan = Internetplan::where('uuid', $uuid)->firstOrFail();
            $affected = $internetplan->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Internetplan does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Internetplan.', 401);
        }
    }
}
