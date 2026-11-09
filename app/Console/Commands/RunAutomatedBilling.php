<?php

namespace App\Console\Commands;

use App\Libraries\Billing\MonthlySubscription;
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
        $failures = app(BillingService::class)->runAutomatedBilling(new MonthlySubscription);

        foreach ($failures as $category => $message) {
            $this->error(date('Y-m-d H:i:s') . " {$category}: {$message}");
        }

        if ($failures !== []) {
            return self::FAILURE;
        }

        $this->info('Automated billing finished on ' . date('Y-m-d H:i:s'));

        return self::SUCCESS;
    }
}
