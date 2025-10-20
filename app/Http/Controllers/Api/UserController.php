<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserRequest\ReplaceUserRequest;
use App\Http\Requests\Api\UserRequest\StoreUserRequest;
use App\Http\Requests\Api\UserRequest\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Policies\Api\UserPolicy;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends ApiController
{
    use ApiResponses;

    protected $policyClass = UserPolicy::class;
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserResource::collection(User::paginate(5));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', User::class);

            return new UserResource(
                User::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a User.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $user = User::with('role')->where('uuid', $uuid)->firstOrFail();
            return new UserResource($user);

        } catch (ModelNotFoundException $ex) {
            return $this->error('User does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a User.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', User::class);

            $user = User::where('uuid', $uuid)->firstOrFail();
            $affected = $user->update($request->mappedAttributes());
            
            return new UserResource($user);

        } catch (ModelNotFoundException $ex) {
            return $this->error('User does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a User.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceUserRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', User::class);
            
            $user = User::where('uuid', $uuid)->firstOrFail();
            $affected = $user->update($request->mappedAttributes());
            
            return new UserResource($user);

        } catch (ModelNotFoundException $ex) {
            return $this->error('User does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a User.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $affected = $user->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('User does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a User.', 401);
        }
    }
}
