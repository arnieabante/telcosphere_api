<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientsDashboardController extends Controller
{
    /**
     * Get client dashboard details by UUID.
     */
    public function show(Request $request, string $uuid)
    {
        $client = Client::with([
            'internetPlan',
        ])
        ->where('uuid', $uuid)
        ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        return new ClientResource($client);
    }
}
