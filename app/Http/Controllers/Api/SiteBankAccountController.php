<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SiteBankAccountRequest\ReplaceSiteBankAccountRequest;
use App\Http\Requests\Api\SiteBankAccountRequest\StoreSiteBankAccountRequest;
use App\Http\Requests\Api\SiteBankAccountRequest\UpdateSiteBankAccountRequest;
use App\Http\Resources\Api\SiteBankAccountResource;
use App\Models\SiteBankAccount;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SiteBankAccountController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SiteBankAccountResource::collection(SiteBankAccount::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiteBankAccountRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Site::class);

            return new SiteBankAccountResource(
                SiteBankAccount::create($request->mappedAttributes())
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
            $site = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
            return new SiteBankAccountResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Site.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(UpdateSiteBankAccountRequest $request, string $uuid)
    {
        try {
            // fetch the site
            $site = SiteBankAccount::where('uuid', $uuid)->firstOrFail();

            // update using the validated/mapped attributes
            $attr = $request->mappedAttributes();

            if ($request->hasFile('companyLogo')) {
                // access/store the image file separeately
                $imgUpload = $_FILES['accountQR'];
                $tmpPath = $imgUpload['tmp_name'];
                $newImgName = uniqid() . '.' . pathinfo($imgUpload['name'], PATHINFO_EXTENSION);

                move_uploaded_file(
                    $tmpPath,
                    storage_path('app/public/' . $newImgName)
                );

                $attr['accountQR'] = $newImgName;
            }

            // update site
            $site->update($attr);

            // return the updated site resource
            return new SiteBankAccountResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Site.', 401);
        }
    }
    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceSiteBankAccountRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Site::class);

            $site = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
            $affected = $site->update($request->mappedAttributes());

            return new SiteBankAccountResource($site);

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
            $site = SiteBankAccount::where('uuid', $uuid)->firstOrFail();
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
            $site = SiteBankAccount::where('site_url', $url)->firstOrFail();
            return new SiteBankAccountResource($site);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Site does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Site.', 401);
        }
    }
}
