<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BillingCategoryRequest\ReplaceBillingCategoryRequest;
use App\Http\Requests\Api\BillingCategoryRequest\StoreBillingCategoryRequest;
use App\Http\Requests\Api\BillingCategoryRequest\UpdateBillingCategoryRequest;
use App\Http\Resources\Api\BillingCategoryResource;
use App\Models\BillingCategory;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BillingCategoryController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return BillingCategoryResource::collection(BillingCategory::paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillingCategoryRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', BillingCategory::class);

            return new BillingCategoryResource(
                BillingCategory::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Billing Category.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $module = BillingCategory::with('roles')->where('uuid', $uuid)->firstOrFail();
            return new BillingCategoryResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Billing Category.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillingCategoryRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', BillingCategory::class);

            $module = BillingCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $module->update($request->mappedAttributes());
            
            return new BillingCategoryResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Billing Category.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceBillingCategoryRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', BillingCategory::class);
            
            $module = BillingCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $module->update($request->mappedAttributes());
            
            return new BillingCategoryResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Billing Category.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $module = BillingCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $module->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Billing Category.', 401);
        }
    }
}
