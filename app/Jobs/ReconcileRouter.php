<?php

namespace App\Jobs;

use App\Models\Mikrotik;
use App\Services\Mikrotik\SubscriberSyncService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/** One job per router, so a dead router cannot hold up another company's. */
class ReconcileRouter implements ShouldQueue
{
    use Queueable;

    // below the database queue's retry_after of 90 seconds
    public int $timeout = 80;

    public function __construct(
        public int $routerId,
        public bool $dryRun = false,
        public bool $force = false,
    ) {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("mikrotik-router-{$this->routerId}"))->releaseAfter(15)->expireAfter(300)];
    }

    public function retryUntil(): CarbonInterface
    {
        // the next hourly sweep takes over after this
        return now()->addMinutes(45);
    }

    public function backoff(): array
    {
        return [60, 180, 300];
    }

    public function handle(SubscriberSyncService $sync): void
    {
        $router = Mikrotik::withoutGlobalScopes()->find($this->routerId);

        if ($router) {
            $sync->syncRouter($router, $this->dryRun, $this->force);
        }
    }
}
