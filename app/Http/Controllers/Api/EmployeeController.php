<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmployeeRequest\ReplaceEmployeeRequest;
use App\Http\Requests\Api\EmployeeRequest\StoreEmployeeRequest;
use App\Http\Requests\Api\EmployeeRequest\UpdateEmployeeRequest;
use App\Http\Resources\Api\EmployeeResource;
use App\Models\Employee;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class EmployeeController extends ApiController
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

        $query = Employee::query();
        if (!empty($include) && $include == 'all') {
            $employees = $query->orderBy('firstname', 'asc')->get();
            return EmployeeResource::collection($employees);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"])
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('date_hired', 'like', "%{$search}%")
                    ->orWhere('access_level', 'like', "%{$search}%")
                    ->orWhere('work_location', 'like', "%{$search}%")
                    ->orWhere('employee_type', 'like', "%{$search}%");
                });
            }
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Employee::class);

            return new EmployeeResource(
                Employee::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Employee.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
           $employee = Employee::with(['role'])->where('uuid', $uuid)->firstOrFail();
            return new EmployeeResource($employee);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Employee does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Employee.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Employee::class);

            $employee = Employee::where('uuid', $uuid)->firstOrFail();
            $affected = $employee->update($request->mappedAttributes());

            return new EmployeeResource($employee);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Employee does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Employee.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceEmployeeRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Employee::class);

            $employee = Employee::where('uuid', $uuid)->firstOrFail();
            $affected = $employee->update($request->mappedAttributes());

            return new EmployeeResource($employee);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Employee does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Employee.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $employee = Employee::where('uuid', $uuid)->firstOrFail();
            $affected = $employee->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Employee does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Employee.', 401);
        }
    }
}
