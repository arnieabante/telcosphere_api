<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use App\Permissions\Abilties;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponses;

   public function login(LoginUserRequest $request)
    {
        $request->validated();

        if (!Auth::attempt($request->only('username', 'password'))) {
            return $this->error('Invalid Credentials.', 400);
        }

        $user = User::with('role')->where('username', $request->username)->first();

        $token = $user->createToken(
            'API Token for ' . $user->email,
            Abilties::getAbilities($user),
            now()->addDay()
        )->plainTextToken;

        return $this->ok('Authenticated.', [
            'token' => $token,
            'user' => [
                'uuid'     => $user->uuid,
                'fullname' => $user->fullname,
                'roleUuid'     => $user->role->uuid ?? null,  
                'role'     => $user->role->name ?? null,  
            ]
        ]);
    }


    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return $this->ok('Logged out.');
    }

    /* User Register is moved to UserController.
    * Only admin acct can create a user
    public function register(UserRequest $request) {
        $request->validated($request->all());

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        return $this->ok('Registered.', [], 201);
        // TODO: include "Location" header field in the response
    }
    */

}
