<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps client portal accounts out of the staff API. Client logins use a known
 * default password, so they must not reach other clients' data.
 */
class EnsureNotClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user?->isClient() || $this->isOwnRoleLookup($request)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'You are not authorized to access this resource.',
            'status' => 403,
        ], 403);
    }

    /**
     * The sidebar reads the menu from the logged in user's role.
     */
    private function isOwnRoleLookup(Request $request): bool
    {
        return $request->routeIs('roles.show')
            && $request->route('role') === $request->user()->role?->uuid;
    }
}
