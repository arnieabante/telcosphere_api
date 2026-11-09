<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileRouter;
use App\Models\Mikrotik;
use App\Models\MikrotikSetting;
use App\Services\Mikrotik\RouterException;
use App\Services\Mikrotik\SubscriberSyncService;
use Illuminate\Console\Command;

class RunNetworkSweep extends Command
{
    protected $signature = 'mikrotik:sweep
        {--router= : Limit to one router id}
        {--dry-run : Report who would be cut off, change no suspensions}
        {--now : Run in this process and print the result instead of queueing}
        {--force : Ignore the disconnect window and circuit breaker}';

    protected $description = 'Disconnect overdue subscribers and reconnect paid ones on every enabled router';

    public function handle(SubscriberSyncService $sync): int
    {
        $routers = Mikrotik::withoutGlobalScopes()
            ->where('is_active', 1)
            ->when($this->option('router'), fn ($query, $id) => $query->where('id', $id))
            ->get()
            ->filter(fn (Mikrotik $router) => MikrotikSetting::forSite($router->site_id)->is_enabled);

        if ($routers->isEmpty()) {
            $this->info('No enabled routers to sweep.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($routers as $router) {
            if (! $this->option('now')) {
                ReconcileRouter::dispatch($router->id, (bool) $this->option('dry-run'), (bool) $this->option('force'));
                $this->line(date('Y-m-d H:i:s')." queued router {$router->id} ({$router->name})");

                continue;
            }

            try {
                $summary = $sync->syncRouter($router, (bool) $this->option('dry-run'), (bool) $this->option('force'));
                $this->line("{$router->name}: ".json_encode($summary));
            } catch (RouterException $ex) {
                $failed = true;
                $this->error("{$router->name}: {$ex->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
