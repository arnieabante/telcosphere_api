<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PricingSettingsRequest\StorePricingSettingsRequest;
use App\Http\Resources\Api\PricingSettingsResource;
use App\Models\PricingSettings;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PricingSettingsController extends ApiController
{
    use ApiResponses;

    /**
     * Display the Pricing settings for the current site.
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

            $pricingSettings = PricingSettings::where(
                'site_id',
                $siteId
            )->first();

            if (!$pricingSettings) {
                return $this->error(
                    'Pricing settings do not exist for this site.',
                    404
                );
            }

            return new PricingSettingsResource(
                $pricingSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to view Pricing settings.',
                401
            );
        }
    }

    /**
     * Create or update Pricing settings.
     */
    public function store(StorePricingSettingsRequest $request)
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
             * Create or update Pricing Settings
             *
             * One configuration per site.
             */
            $pricingSettings = PricingSettings::updateOrCreate(
                [
                    'site_id' => $siteId
                ],
                $attributes
            );

            return new PricingSettingsResource(
                $pricingSettings
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to create or update Pricing settings.',
                401
            );
        }
    }

    /**
     * Remove Pricing settings.
     */
    public function destroy(string $uuid)
    {
        try {

            $pricingSettings =
                PricingSettings::where(
                    'uuid',
                    $uuid
                )->firstOrFail();

            $affected = $pricingSettings->delete();

            return $this->ok(
                "Deleted $affected record.",
                []
            );

        } catch (ModelNotFoundException $ex) {

            return $this->error(
                'Pricing settings do not exist.',
                404
            );

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to delete Pricing settings.',
                401
            );
        }
    }
}