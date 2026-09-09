<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CtaSettingsRequest\StoreCtaSettingsRequest;
use App\Http\Requests\Api\CtaSettingsRequest\UpdateCtaSettingsRequest;
use App\Http\Resources\Api\CtaSettingsResource;
use App\Models\CtaSettings;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CtaSettingsController extends ApiController
{
    use ApiResponses;

    /**
     * Display CTA settings for the current site.
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

            $ctaSettings = CtaSettings::where(
                'site_id',
                $siteId
            )->first();

            if (!$ctaSettings) {
                return $this->error(
                    'CTA settings do not exist for this site.',
                    404
                );
            }

            return new CtaSettingsResource(
                $ctaSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view CTA settings.',
                401
            );
        }
    }

    /**
     * Create or update CTA settings.
     *
     * POST is used for both create and update.
     */
    public function store(StoreCtaSettingsRequest $request)
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
             * Create or update CTA settings
             */
            $ctaSettings = CtaSettings::updateOrCreate(
                [
                    'site_id' => $siteId
                ],
                $attributes
            );

            return new CtaSettingsResource(
                $ctaSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to create or update CTA settings.',
                401
            );
        }
    }

    /**
     * Display a specific CTA setting.
     */
    public function show(string $uuid)
    {
        try {

            $ctaSettings = CtaSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            return new CtaSettingsResource(
                $ctaSettings
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'CTA settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view CTA settings.',
                401
            );
        }
    }

    /**
     * Update CTA settings.
     */
    public function update(
        UpdateCtaSettingsRequest $request,
        string $uuid
    ) {
        try {

            $ctaSettings = CtaSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            $ctaSettings->update(
                $request->mappedAttributes()
            );

            return new CtaSettingsResource(
                $ctaSettings
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'CTA settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to update CTA settings.',
                401
            );
        }
    }

    /**
     * Remove CTA settings.
     */
    public function destroy(string $uuid)
    {
        try {

            $ctaSettings = CtaSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            $affected = $ctaSettings->delete();

            return $this->ok(
                "Deleted $affected record.",
                []
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'CTA settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to delete CTA settings.',
                401
            );
        }
    }
}