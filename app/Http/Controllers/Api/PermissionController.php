<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Role;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function attachModule(string $role_uuid, string $module_uuid, Request $request)
    {
        try {
            $role = Role::where('uuid', $role_uuid)->firstOrFail();
            $module = Module::where('uuid', $module_uuid)->firstOrFail();

            $role->modules()->attach($module->id, [
                'is_read' => $request->input('isRead'),
                'is_write' => $request->input('isWrite'),
                'is_delete' => $request->input('isDelete'),
                'is_active' => $request->input('isActive'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return response()->json(['message' => 'Attach Module is successful.']);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Role or Module does not exist.', 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function detachModule(string $role_uuid, string $module_uuid, Request $request)
    {
        try {
            $role = Role::where('uuid', $role_uuid)->firstOrFail();
            $module = Module::where('uuid', $module_uuid)->firstOrFail();

            if (Role::where('uuid', $role_uuid)->whereAttachedTo($module)) { // TODO: condition is not yet working
                $role->modules()->detach($module->id);
                return response()->json(['message' => 'Detach Module is successful.']);
            } else
                return $this->error('Module is not attached to the Role.', 404);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Role or Module does not exist.', 404);
        }
        
    }
}
