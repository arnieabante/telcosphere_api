<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SmsDeliveryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmsSettingsRequest\BaseSmsSettingsRequest;
use App\Http\Requests\Api\SmsSettingsRequest\ReplaceSmsSettingsRequest;
use App\Http\Requests\Api\SmsSettingsRequest\StoreSmsSettingsRequest;
use App\Http\Requests\Api\SmsSettingsRequest\UpdateSmsSettingsRequest;
use App\Http\Resources\Api\SmsSettingsResource;
use App\Models\SmsLog;
use App\Models\SmsSettings;
use App\Services\SmsNotificationService;
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
    public function index()
    {
        try {

            $smsSettings = SmsSettings::where(
                'site_id',
                auth()->user()->site_id
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
             * A site can only ever configure itself
             */
            $siteId = auth()->user()->site_id;

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
        BaseSmsSettingsRequest $request,
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
     * Replace SMS settings.
     */
    public function replace(
        ReplaceSmsSettingsRequest $request,
        string $uuid
    ) {
        return $this->update($request, $uuid);
    }

    /**
     * Send a test SMS using the site's saved settings.
     */
    public function test(
        Request $request,
        SmsNotificationService $service
    ) {
        $data = $request->validate([
            'number' => 'required|string|max:20'
        ]);

        $settings = SmsSettings::where(
            'site_id',
            auth()->user()->site_id
        )->first();

        if (!$settings || !$settings->is_enabled) {

            return $this->error(
                'SMS is turned off for this site. Turn on Enable SMS and save first.',
                422
            );
        }

        if (!$settings->test_mode && blank($settings->api_key)) {

            return $this->error(
                'Save your Semaphore API key first.',
                422
            );
        }

        $number = $service->normalizeNumber($data['number']);

        if (!$number) {

            return $this->error(
                'Enter a valid Philippine mobile number.',
                422
            );
        }

        try {

            $log = $service->sendTest($settings, $number);

        } catch (SmsDeliveryException $ex) {

            return $this->error($ex->getMessage(), 502);
        }

        return $this->ok(
            $log->status === SmsLog::STATUS_SIMULATED
                ? 'Test mode is on: the SMS was simulated and not sent.'
                : 'Test SMS sent.',
            ['status' => $log->status]
        );
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