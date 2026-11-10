<?php

namespace App\Console\Commands;

use App\Libraries\Billing\MonthlySubscription;
use App\Models\Site;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Throwable;

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
        $this->info('Automated billing started on ' . date('Y-m-d H:i:s'));

        foreach (Site::where('is_active', 1)->pluck('id') as $siteId) {
            $administrator = $this->administratorOf($siteId);

            if (!$administrator) {
                $this->warn("Site {$siteId}: skipped, it has no active Administrator user.");
                continue;
            }

            // billing code and SiteScope work from the logged-in user, which a scheduled command lacks
            auth()->setUser($administrator);

            try {
                $outcomes = app(BillingService::class)->runAutomatedBilling(new MonthlySubscription);
                $this->info("Site {$siteId}: " . ($outcomes ? json_encode($outcomes) : 'no billing cycle today'));
            } catch (Throwable $ex) {
                report($ex);
                $this->error("Site {$siteId}: {$ex->getMessage()}");
            } finally {
                auth()->forgetUser();
            }
        }
    }

    private function administratorOf(int $siteId): ?User
    {
        return User::where('site_id', $siteId)
            ->where('is_active', 1)
            ->whereHas('role', fn ($query) => $query->where('description', 'Administrator'))
            ->orderBy('id')
            ->first();
    }
}
