<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\MikrotikLog;
use App\Services\Mikrotik\SubscriberSyncService;
use App\Services\Mikrotik\SyncOptions;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/** Holds only ids and a username: credentials never go into the queue table. */
class ReconcileClient implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function __construct(
        public int $clientId,
        public int $routerId,
        public string $trigger,
        public bool $syncPassword = false,
        public ?string $previousUsername = null,
        public ?int $actorId = null,
    ) {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("mikrotik-router-{$this->routerId}"))->releaseAfter(15)->expireAfter(300)];
    }

    public function retryUntil(): CarbonInterface
    {
        return now()->addHours(6);
    }

    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function handle(SubscriberSyncService $sync): void
    {
        $client = Client::withoutGlobalScopes()->with('internetPlan')->find($this->clientId);

        if (! $client) {
            return;
        }

        $sync->syncClient($client, new SyncOptions(
            trigger: $this->trigger,
            syncPassword: $this->syncPassword,
            previousUsername: $this->previousUsername,
            actorId: $this->actorId,
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $client = Client::withoutGlobalScopes()->find($this->clientId);

        if ($client) {
            MikrotikLog::withoutGlobalScopes()->create([
                'site_id' => $client->site_id,
                'mikrotik_id' => $this->routerId,
                'client_id' => $client->id,
                'pppoe_username' => $client->pppoe_username,
                'action' => 'sync',
                'result' => MikrotikLog::RESULT_FAILED,
                'trigger' => $this->trigger,
                'message' => $exception?->getMessage(),
                'actor_id' => $this->actorId,
            ]);
        }
    }
}
