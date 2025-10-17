<?php

namespace App\Policies\Api;

use App\Models\User;
use App\Permissions\Abilties;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user) : bool {
        return $user->tokenCan(Abilties::CreateUser);
    }

    public function update(User $user) : bool {
        return $user->tokenCan(Abilties::UpdateUser);
    }
}
