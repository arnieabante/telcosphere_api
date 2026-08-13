<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HomepageSettingsRequest\ReplaceHomepageSettingsRequest;
use App\Http\Requests\Api\HomepageSettingsRequest\StoreHomepageSettingsRequest;
use App\Http\Requests\Api\HomepageSettingsRequest\UpdateHomepageSettingsRequest;
use App\Http\Resources\Api\HomepageSettingsResource;
use App\Models\HomepageSettings; 
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class HomepageSettingsController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $siteId = $request->get('site_id')
                ?? $request->header('site_id')
                ?? auth()->user()->site_id
                ?? null;

            if (!$siteId) {
                return $this->error('Site ID is required.', 400);
            }

            $homepageSettings = HomepageSettings::where('site_id', $siteId)
                ->first();

            if (!$homepageSettings) {
                return $this->error(
                    'Homepage settings do not exist for this site.',
                    404
                );
            }

            return new HomepageSettingsResource($homepageSettings);

        } catch (AuthorizationException $ex) {
            return $this->error(
                'You are not authorized to view HomepageSettings.',
                401
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHomepageSettingsRequest $request)
    {
        try {
            // $this->isAble('create', HomepageSettings::class);

            $attributes = $request->mappedAttributes();

            $homepageSettings = HomepageSettings::updateOrCreate(
                [
                    'site_id' => $attributes['site_id']
                ],
                $attributes
            );

            return new HomepageSettingsResource($homepageSettings);

        } catch (AuthorizationException $ex) {
            return $this->error(
                'You are not authorized to create or update HomepageSettings.',
                401
            );
        }
    }
    
    public function destroy(string $uuid)
    {
        try {
            $homepagesettings = HomepageSettings::where('uuid', $uuid)->firstOrFail();
            $affected = $homepagesettings->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('HomepageSettings does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a HomepageSettings.', 401);
        }
    }
}
