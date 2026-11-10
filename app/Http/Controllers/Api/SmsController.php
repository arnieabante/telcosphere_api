<?php

namespace App\Http\Controllers\Api;

use App\Models\SmsSettings;
use App\Services\SmsNotificationService;
use App\Traits\ApiResponses;

class SmsController extends ApiController
{
    use ApiResponses;

    /**
     * Send overdue and disconnection-day reminders for the logged-in user's site.
     */
    public function overdueReminders(SmsNotificationService $service)
    {
        $settings = SmsSettings::forSite((int) auth()->user()->site_id);

        if (!$settings || !$settings->canSend()) {
            return $this->error('SMS is not enabled for this site.', 422);
        }

        return $this->ok('Overdue reminders processed.', $service->sendOverdueReminders($settings));
    }
}
