<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmsSettingsRequest\StoreSmsSettingsRequest;
use App\Http\Requests\Api\SmsSettingsRequest\UpdateSmsSettingsRequest;
use App\Http\Resources\Api\SmsSettingsResource;
use App\Models\SmsSettings;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SmsSettingsController extends ApiController
{
    use ApiResponses;

    /**
     * Display SMS settings for the current site.
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

            $smsSettings = SmsSettings::where(
                'site_id',
                $siteId
            )->first();

            if (!$smsSettings) {

                return $this->error(
                    'SMS settings do not exist for this site.',
                    404
                );

            }

            return new SmsSettingsResource(
                $smsSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view SMS settings.',
                401
            );
        }
    }

    /**
     * Create or update SMS settings.
     *
     * POST is used for both create and update.
     */
    public function store(StoreSmsSettingsRequest $request)
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
                ?? null;

            if (!$siteId) {

                return $this->error(
                    'Site ID is required.',
                    400
                );

            }

            $attributes['site_id'] = $siteId;

            /*
             * Create or update SMS settings
             */
            $smsSettings = SmsSettings::updateOrCreate(
                [
                    'site_id' => $siteId
                ],
                $attributes
            );

            return new SmsSettingsResource(
                $smsSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to create or update SMS settings.',
                401
            );
        }
    }

    /**
     * Display a specific SMS setting.
     */
    public function show(string $uuid)
    {
        try {

            $smsSettings = SmsSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            return new SmsSettingsResource(
                $smsSettings
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'SMS settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view SMS settings.',
                401
            );
        }
    }

    /**
     * Update SMS settings.
     */
    public function update(
        UpdateSmsSettingsRequest $request,
        string $uuid
    ) {
        try {

            $smsSettings = SmsSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            $smsSettings->update(
                $request->mappedAttributes()
            );

            return new SmsSettingsResource(
                $smsSettings
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'SMS settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to update SMS settings.',
                401
            );
        }
    }

    /**
     * Remove SMS settings.
     */
    public function destroy(string $uuid)
    {
        try {

            $smsSettings = SmsSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            $affected = $smsSettings->delete();

            return $this->ok(
                "Deleted $affected record.",
                []
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'SMS settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to delete SMS settings.',
                401
            );
        }
    }
}