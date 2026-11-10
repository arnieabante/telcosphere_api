<?php

namespace App\Console\Commands;

use App\Models\Scopes\SiteScope;
use App\Models\SmsSettings;
use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Throwable;

class SendOverdueSms extends Command
{
    protected $signature = 'app:send-overdue-sms';

    protected $description = 'Queues overdue and disconnection-day SMS reminders for every site with SMS enabled';

    public function handle(SmsNotificationService $service): void
    {
        $sites = SmsSettings::withoutGlobalScope(SiteScope::class)->where('is_enabled', 1)->get();

        foreach ($sites as $settings) {
            if (!$settings->canSend()) {
                continue;
            }

            // one site's failure must not stop the other companies' reminders
            try {
                $this->info("Site {$settings->site_id}: " . json_encode($service->sendOverdueReminders($settings)));
            } catch (Throwable $ex) {
                report($ex);
                $this->error("Site {$settings->site_id}: {$ex->getMessage()}");
            }
        }
    }
}
