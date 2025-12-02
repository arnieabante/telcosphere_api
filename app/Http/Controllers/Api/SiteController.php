<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SiteRequest\ReplaceSiteRequest;
use App\Http\Requests\Api\SiteRequest\StoreSiteRequest;
use App\Http\Requests\Api\SiteRequest\UpdateSiteRequest;
use App\Http\Resources\Api\SiteResource;
use App\Models\Site;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SiteController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SiteResource::collection(Site::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Site::class);

            return new SiteResource(
                Site::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Site.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $site = Site::where('uuid', $uuid)->firstOrFail();
            return new SiteResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Site.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiteRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', Site::class);

            $site = Site::where('uuid', $uuid)->firstOrFail();
            $affected = $site->update($request->mappedAttributes());
            
            return new SiteResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Site.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceSiteRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Site::class);
            
            $site = Site::where('uuid', $uuid)->firstOrFail();
            $affected = $site->update($request->mappedAttributes());
            
            return new SiteResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Site.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $site = Site::where('uuid', $uuid)->firstOrFail();
            $affected = $site->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Site.', 401);
        }
    }

        /**
     * Display the specified resource via link.
     */
    public function showByUrl(string $url)
    {
        try {
            $site = Site::where('site_url', $url)->firstOrFail();
            return new SiteResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Site.', 401);
        }
    }
}
