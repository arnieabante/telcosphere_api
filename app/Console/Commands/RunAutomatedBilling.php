<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class RunAutomatedBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-automated-billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes Automated Subscription Billing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            app(BillingService::class)->runAutomatedBilling();
            $this->info('Automated billing generated successfully on ' . date('Y-m-d H:i:s'));
        } catch (\Exception $ex) {
            $this->info('No billing generated on ' . date('Y-m-d H:i:s') . ': ' . $ex);
        }
    }
}
