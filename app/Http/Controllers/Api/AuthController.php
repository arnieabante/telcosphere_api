<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginUserRequest;
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

        $siteId = $request->siteId;

        /*
        |--------------------------------------------------------------------------
        | Find user by username + site
        |--------------------------------------------------------------------------
        */
        $user = User::withoutGlobalScopes()
            ->with('role.modules')
            ->where('username', $request->username)
            ->where('site_id', $siteId)
            ->first();

        if (!$user) {
            return $this->error('Invalid Credentials.', 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check active status
        |--------------------------------------------------------------------------
        */
        if (!$user->is_active) {
            return $this->error('Your account is inactive.', 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Check password
        |--------------------------------------------------------------------------
        */
        if (!Auth::attempt([
            'site_id' => $user->site_id,
            'username' => $user->username,
            'password' => $request->password,
        ])) {
            return $this->error('Invalid Credentials.', 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check role
        |--------------------------------------------------------------------------
        */
        if (!$user->role) {
            return $this->error('Your account does not have a role assigned.', 403);
        }

        if ($user->isClient() && !$user->client) {
            return $this->error('Your account is not linked to a client.', 403);
        }

        if ($user->isClient() && !$user->client->is_active) {
            return $this->error('Your account is inactive.', 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum token
        |--------------------------------------------------------------------------
        */
        $token = $user->createToken(
            'API Token for ' . $user->email,
            Abilties::getAbilities($user),
            now()->addDay()
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | First module
        |--------------------------------------------------------------------------
        */
        $firstModule = $user->role->modules->first();

        return $this->ok('Authenticated.', [
            'token' => $token,

            'user' => [
                'uuid'       => $user->uuid,
                'fullname'   => $user->fullname,
                'username'   => $user->username,
                'email'      => $user->email,

                'roleUuid'   => $user->role->uuid,
                'role'       => $user->role->name,

                'firstModule' => $firstModule?->url,

                'clientUuid' => $user->client?->uuid,

                'mustChangePassword' => $user->must_change_password,
            ],
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ]);

        // anyone else logged in with the old password is signed out
        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id ?? 0)
            ->delete();

        return $this->ok('Password changed.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok('Logged out.');
    }
}
