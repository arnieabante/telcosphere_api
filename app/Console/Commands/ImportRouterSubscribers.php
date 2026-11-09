<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Mikrotik;
use App\Services\Mikrotik\RouterClientFactory;
use App\Services\Mikrotik\RouterException;
use App\Services\Mikrotik\SubscriberSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Onboards a router that already has subscribers: links clients to it by PPPoE
 * username. It only reads from the router and reports what it cannot match.
 */
class ImportRouterSubscribers extends Command
{
    protected $signature = 'mikrotik:import {router : Router id} {--apply : Save the matches (default is a report only)}';

    protected $description = 'Match the PPPoE secrets on a router to clients by username';

    public function handle(RouterClientFactory $factory): int
    {
        $router = Mikrotik::withoutGlobalScopes()->find($this->argument('router'));

        if (! $router) {
            $this->error('Router not found.');

            return self::FAILURE;
        }

        try {
            $api = $factory->forRouter($router);
            $secrets = $api->secrets();
            $api->close();
        } catch (RouterException $ex) {
            $this->error($ex->getMessage());

            return self::FAILURE;
        }

        $clients = Client::withoutGlobalScopes()
            ->where('site_id', $router->site_id)
            ->where('is_active', 1)
            ->whereNotNull('pppoe_username')
            ->where('pppoe_username', '!=', '')
            ->get(['id', 'pppoe_username', 'server_id', 'mikrotik_id']);

        $byUsername = $clients->groupBy('pppoe_username');
        $matched = [];
        $problems = [];

        foreach ($secrets as $name => $secret) {
            $candidates = $byUsername->get($name);

            if (! $candidates) {
                $problems[] = ['On router, no client', $name];
            } elseif ($candidates->count() > 1) {
                $problems[] = ['Several clients share this username', $name];
            } elseif ($candidates->first()->mikrotik_id && (int) $candidates->first()->mikrotik_id !== $router->id) {
                $problems[] = ['Client already linked to another router', $name];
            } elseif ($router->server_id && (int) $candidates->first()->server_id !== (int) $router->server_id) {
                $problems[] = ["Client's server differs from the router's", $name];
            } else {
                $matched[] = [$candidates->first(), $secret];
            }
        }

        foreach ($clients->where('mikrotik_id', $router->id) as $client) {
            if (! isset($secrets[$client->pppoe_username])) {
                $problems[] = ['Client linked here, not on router', $client->pppoe_username];
            }
        }

        if ($this->option('apply')) {
            foreach ($matched as [$client, $secret]) {
                DB::table('clients')->where('id', $client->id)->update([
                    'mikrotik_id' => $router->id,
                    'network_status' => ($secret['disabled'] ?? 'false') === 'true'
                        ? SubscriberSyncService::STATUS_DISABLED_EXTERNALLY
                        : SubscriberSyncService::STATUS_ACTIVE,
                    'network_synced_at' => now(),
                    'network_error' => null,
                ]);
            }
        }

        $this->info(count($matched).' matched'.($this->option('apply') ? ' and linked.' : ' (report only, add --apply to save).'));

        if ($problems !== []) {
            $this->table(['Problem', 'PPPoE username'], $problems);
        }

        return self::SUCCESS;
    }
}
