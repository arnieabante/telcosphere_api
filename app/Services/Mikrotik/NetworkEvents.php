<?php

namespace App\Services\Mikrotik;

use App\Jobs\ReconcileClient;
use App\Jobs\RemoveRouterSubscriber;
use App\Models\Client;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Turns business events into router work. Everything is queued after the
 * database commits and failures are only reported: a router or queue problem
 * must never fail a payment or a client save.
 */
class NetworkEvents
{
    /** @return array{mikrotik_id: mixed, pppoe_username: mixed, synced: bool} */
    public static function snapshot(Client $client): array
    {
        return [
            'mikrotik_id' => $client->mikrotik_id,
            'pppoe_username' => $client->pppoe_username,
            'synced' => (bool) $client->network_synced_at,
        ];
    }

    /** @param array{mikrotik_id: mixed, pppoe_username: mixed, synced: bool}|null $before */
    public static function clientSaved(Client $client, ?array $before = null): void
    {
        $actorId = auth()->id();
        $previousRouter = $before['mikrotik_id'] ?? null;
        $previousUsername = $before['pppoe_username'] ?? null;
        $wasOnRouter = $previousRouter && $previousUsername && ($before['synced'] ?? false);
        $sameRouter = (int) $previousRouter === (int) $client->mikrotik_id;
        $hasTarget = $client->mikrotik_id && trim((string) $client->pppoe_username) !== '';

        if ($wasOnRouter && (! $hasTarget || ! $sameRouter)) {
            self::queue(fn () => RemoveRouterSubscriber::dispatch(
                (int) $previousRouter, (int) $client->site_id, $previousUsername, $client->id, SyncOptions::TRIGGER_CLIENT, $actorId
            ));
        }

        if (! $hasTarget) {
            return;
        }

        $renamed = $wasOnRouter && $sameRouter && $previousUsername !== $client->pppoe_username;
        $syncPassword = $client->wasChanged('pppoe_password');

        self::queue(fn () => ReconcileClient::dispatch(
            $client->id,
            (int) $client->mikrotik_id,
            SyncOptions::TRIGGER_CLIENT,
            $syncPassword,
            $renamed ? $previousUsername : null,
            $actorId
        ));
    }

    /** @param array{mikrotik_id: mixed, pppoe_username: mixed, synced: bool} $snapshot taken before the delete */
    public static function clientDeleted(int $clientId, int $siteId, array $snapshot): void
    {
        if (! $snapshot['mikrotik_id'] || ! $snapshot['pppoe_username'] || ! $snapshot['synced']) {
            return;
        }

        $actorId = auth()->id();

        self::queue(fn () => RemoveRouterSubscriber::dispatch(
            (int) $snapshot['mikrotik_id'], $siteId, $snapshot['pppoe_username'], $clientId, SyncOptions::TRIGGER_CLIENT, $actorId
        ));
    }

    public static function requestSync(?int $clientId, string $trigger): void
    {
        if (! $clientId) {
            return;
        }

        $routerId = Client::withoutGlobalScopes()->whereKey($clientId)->value('mikrotik_id');

        if (! $routerId) {
            return;
        }

        $actorId = auth()->id();

        self::queue(fn () => ReconcileClient::dispatch($clientId, (int) $routerId, $trigger, false, null, $actorId));
    }

    private static function queue(Closure $dispatch): void
    {
        DB::afterCommit(function () use ($dispatch) {
            try {
                $dispatch();
            } catch (\Throwable $ex) {
                report($ex);
            }
        });
    }
}
