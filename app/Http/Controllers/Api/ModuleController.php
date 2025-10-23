<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ModuleRequest\ReplaceModuleRequest;
use App\Http\Requests\Api\ModuleRequest\StoreModuleRequest;
use App\Http\Requests\Api\ModuleRequest\UpdateModuleRequest;
use App\Http\Resources\Api\ModuleResource;
use App\Models\Module;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ModuleController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ModuleResource::collection(Module::paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModuleRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Module::class);

            return new ModuleResource(
                Module::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Module.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $module = Module::with('roles')->where('uuid', $uuid)->firstOrFail();
            return new ModuleResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Module does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Module.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModuleRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', Module::class);

            $module = Module::where('uuid', $uuid)->firstOrFail();
            $affected = $module->update($request->mappedAttributes());
            
            return new ModuleResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Module does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Module.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceModuleRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Module::class);
            
            $module = Module::where('uuid', $uuid)->firstOrFail();
            $affected = $module->update($request->mappedAttributes());
            
            return new ModuleResource($module);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Module does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Module.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $module = Module::where('uuid', $uuid)->firstOrFail();
            $affected = $module->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Module does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Module.', 401);
        }
    }
}
