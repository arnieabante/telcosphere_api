<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RoleRequest\ReplaceRoleRequest;
use App\Http\Requests\Api\RoleRequest\StoreRoleRequest;
use App\Http\Requests\Api\RoleRequest\UpdateRoleRequest;
use App\Http\Resources\Api\RoleResource;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Role::query()->where('is_active', '=', '1');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Role::class);

            return new RoleResource(
                Role::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Role.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $role = Role::with('modules')->where('uuid', $uuid)->firstOrFail();
            return new RoleResource($role);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Role does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Role.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', Role::class);

            $role = Role::where('uuid', $uuid)->firstOrFail();
            $affected = $role->update($request->mappedAttributes());
            
            return new RoleResource($role);

        } catch (ModelNotFoundException $ex) {
            return $this->error('User does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Role.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceRoleRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Role::class);
            
            $role = Role::where('uuid', $uuid)->firstOrFail();
            $affected = $role->update($request->mappedAttributes());
            
            return new RoleResource($role);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Roles does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Role.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $role = Role::where('uuid', $uuid)->firstOrFail();
            $affected = $role->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Role does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Role.', 401);
        }
    }

    public function attach(Request $request, string $uuid) 
    {
        $role = Role::where('uuid', $uuid)->firstOrFail();
        $currentModules = [];
        foreach ($role->modules as $module)
            $currentModules[] = $module->uuid;
        
        $inputModules = $request->input('modules');
        $toUpdate = $toCreate = [];

        foreach ($inputModules as $input) {
            if (in_array($input['uuid'], $currentModules)) {
                // update existing
                $toUpdate[$input['uuid']] =  [
                    'is_read' => $input['isRead'],
                    'is_write' => $input['isWrite'],
                    'is_delete' => $input['isDelete'],
                ];
                
            } else {
                try {
                    // confirm existence
                    $module = Module::where('uuid', $input['uuid'])->firstOrFail();
                    // create new
                    $toCreate[$module['id']] = [
                        'is_read' => $input['isRead'],
                        'is_write' => $input['isWrite'],
                        'is_delete' => $input['isDelete'],
                        'is_active' => $input['isActive'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                } catch (ModelNotFoundException $ex) {
                    return $this->error('Module does not exist.', 404);
                }
            }
        }

        if (!empty($toUpdate))
            foreach ($role->modules as $module)
                if (isset($toUpdate[$module->uuid]))
                    $module->pivot->update($toUpdate[$module->uuid]);

        if (!empty($toCreate))
            foreach ($toCreate as $id => $attributes)
                $role->modules()->attach($id, $attributes);
        
        return new RoleResource($role->load('modules'));
    }

    public function detach(Request $request, string $uuid)
    {
        try {
            $role = Role::where('uuid', $uuid)->firstOrFail();
            $currentModules = [];
            foreach ($role->modules as $module)
                $currentModules[] = $module->uuid;

            $inputModules = $request->input('modules');
            foreach ($inputModules as $input) {
                // confirm existence
                $module = Module::where('uuid', $input['uuid'])->firstOrFail();
                // remove from role
                if (in_array($module['uuid'], $currentModules))
                    $role->modules()->detach($module['id']);
            }
            
            return new RoleResource($role->load('modules'));

        } catch (ModelNotFoundException $ex) {
            return $this->error('Role or Module does not exist.', 404);
        }   
    }
}
