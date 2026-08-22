<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FooterSettingsRequest\StoreFooterSettingsRequest;
use App\Http\Requests\Api\FooterSettingsRequest\UpdateFooterSettingsRequest;
use App\Http\Resources\Api\FooterSettingsResource;
use App\Models\FooterSettings;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class FooterSettingsController extends ApiController
{
    use ApiResponses;

    /**
     * Display footer settings for the current site.
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

            $footerSettings = FooterSettings::where(
                'site_id',
                $siteId
            )->first();

            if (!$footerSettings) {
                return $this->error(
                    'Footer settings do not exist for this site.',
                    404
                );
            }

            return new FooterSettingsResource(
                $footerSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view Footer settings.',
                401
            );
        }
    }

    /**
     * Create or update footer settings.
     *
     * POST is used for both create and update.
     */
    public function store(StoreFooterSettingsRequest $request)
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
             * Create or update Footer settings
             */
            $footerSettings = FooterSettings::updateOrCreate(
                [
                    'site_id' => $siteId
                ],
                $attributes
            );

            return new FooterSettingsResource(
                $footerSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to create or update Footer settings.',
                401
            );
        }
    }
    
    public function destroy(string $uuid)
    {
        try {

            $footerSettings = FooterSettings::where(
                'uuid',
                $uuid
            )->firstOrFail();

            $affected = $footerSettings->delete();

            return $this->ok(
                "Deleted $affected record.",
                []
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'Footer settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to delete Footer settings.',
                401
            );
        }
    }
}