<?php

namespace App\Console\Commands;

use App\Libraries\Notification\Sms;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RunAutomatedSMSNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-automated-sms-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes Automated SMS Billing Notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            app(NotificationService::class)->runAutomatedNotification(new Sms);
            $this->info('Automated SMS Notifications sent successfully on ' . date('Y-m-d H:i:s'));
        } catch (\Exception $ex) {
            $this->info('No SMS Notification sent on ' . date('Y-m-d H:i:s') . ': ' . $ex);
        }
    }
}
