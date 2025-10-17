<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        'App\Models\Item' => 'App\Policies\Api\ItemPolicy',
        'App\Models\User' => 'App\Policies\Api\UserPolicy'
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Define any Gates or auth logic here
    }
}
