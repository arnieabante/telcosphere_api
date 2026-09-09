<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AboutUsSettingsRequest\StoreAboutUsSettingsRequest;
use App\Http\Resources\Api\AboutUsSettingsResource;
use App\Models\AboutUsSettings;
use App\Services\ImageUploadService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AboutUsSettingsController extends ApiController
{
    use ApiResponses;

    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

    /**
     * Display the About Us settings for the current site.
     */
    public function index(Request $request)
    {
        try {

            $siteId = $request->get('site_id')
                ?? $request->header('site_id')
                ?? auth()->user()->site_id
                ?? null;

            if (!$siteId) {
                return $this->error(
                    'Site ID is required.',
                    400
                );
            }

            $aboutUsSettings = AboutUsSettings::where(
                'site_id',
                $siteId
            )->first();

            if (!$aboutUsSettings) {
                return $this->error(
                    'About Us settings do not exist for this site.',
                    404
                );
            }

            return new AboutUsSettingsResource(
                $aboutUsSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view About Us settings.',
                401
            );
        }
    }

    /**
     * Create or update About Us settings.
     */
    public function store(StoreAboutUsSettingsRequest $request)
    {
        try {

            $attributes = $request->mappedAttributes();

            /*
             * Get site ID
             */
            $siteId = $attributes['site_id']
                ?? $request->get('siteId')
                ?? $request->header('site_id')
                ?? auth()->user()->site_id
                ?? 1;

            $attributes['site_id'] = $siteId;

            /*
             * About Us Image
             */
            if ($request->hasFile('aboutUsImage')) {

                $attributes['about_us_image'] =
                    $this->imageUploadService->upload(
                        $request->file('aboutUsImage')
                    );
            }

            /*
             * Create or update About Us Settings
             *
             * One configuration per site.
             */
            $aboutUsSettings = AboutUsSettings::updateOrCreate(
                [
                    'site_id' => $siteId
                ],
                $attributes
            );

            return new AboutUsSettingsResource(
                $aboutUsSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to create or update About Us settings.',
                401
            );
        }
    }

    /**
     * Remove About Us settings.
     */
    public function destroy(string $uuid)
    {
        try {

            $aboutUsSettings =
                AboutUsSettings::where(
                    'uuid',
                    $uuid
                )->firstOrFail();

            $affected = $aboutUsSettings->delete();

            return $this->ok(
                "Deleted $affected record.",
                []
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'About Us settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to delete About Us settings.',
                401
            );
        }
    }
}