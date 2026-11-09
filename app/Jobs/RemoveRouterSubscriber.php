<?php

namespace App\Jobs;

use App\Models\MikrotikLog;
use App\Services\Mikrotik\SubscriberSyncService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class RemoveRouterSubscriber implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function __construct(
        public int $routerId,
        public int $siteId,
        public string $username,
        public ?int $clientId,
        public string $trigger,
        public ?int $actorId = null,
    ) {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("mikrotik-router-{$this->routerId}"))->releaseAfter(15)->expireAfter(300)];
    }

    public function retryUntil(): CarbonInterface
    {
        // the sweep cannot redo this one: the client no longer points at this router
        return now()->addDays(3);
    }

    public function backoff(): array
    {
        return [30, 60, 120, 300, 600, 1800];
    }

    public function handle(SubscriberSyncService $sync): void
    {
        $sync->removeSubscriber($this->routerId, $this->siteId, $this->username, $this->clientId, $this->trigger, $this->actorId);
    }

    public function failed(?Throwable $exception): void
    {
        MikrotikLog::withoutGlobalScopes()->create([
            'site_id' => $this->siteId,
            'mikrotik_id' => $this->routerId,
            'client_id' => $this->clientId,
            'pppoe_username' => $this->username,
            'action' => 'remove',
            'result' => MikrotikLog::RESULT_FAILED,
            'trigger' => $this->trigger,
            'message' => $exception?->getMessage(),
            'actor_id' => $this->actorId,
        ]);
    }
}
