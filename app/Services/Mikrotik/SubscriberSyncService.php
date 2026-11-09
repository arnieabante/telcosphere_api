<?php

namespace App\Services\Mikrotik;

use App\Models\Client;
use App\Models\Mikrotik;
use App\Models\MikrotikLog;
use App\Models\MikrotikSetting;
use Illuminate\Support\Facades\DB;

/**
 * Makes a router match the database: work out what a subscriber should look
 * like, compare with what the router has, apply only the difference. Safe to
 * repeat, so a failed call is simply fixed by the next retry or sweep.
 */
class SubscriberSyncService
{
    public const STATUS_UNMANAGED = 'unmanaged';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DISABLED_EXTERNALLY = 'disabled_externally';

    // the circuit breaker never trips below this many cuts in one run
    private const BREAKER_MINIMUM = 5;

    /** @var array<int, true> clients already reported today by a dry run */
    private array $dryRunLogged = [];

    public function __construct(
        private RouterClientFactory $factory,
        private SuspensionPolicy $policy,
    ) {
    }

    public function syncClient(Client $client, SyncOptions $options): void
    {
        $router = $this->routerFor($client);

        if (! $router || ! $this->hasUsername($client)) {
            return;
        }

        $settings = MikrotikSetting::forSite($router->site_id);

        if (! $settings->is_enabled) {
            return;
        }

        try {
            $api = $this->connect($router);
        } catch (RouterConnectionException $ex) {
            $this->mark($client, ['network_error' => $ex->getMessage()]);

            throw $ex;
        }

        try {
            $secret = $api->secret($client->pppoe_username);

            if (! $secret && $options->previousUsername) {
                $secret = $api->secret($options->previousUsername);
            }

            $overdue = (bool) $client->is_active && $this->policy->isOverdue($client, $settings->today());

            $this->reconcile($client, $router, $api, $secret, null, $overdue, $options);
        } finally {
            $api->close();
        }
    }

    /**
     * @return array{skipped: bool, checked: int, suspended: int, reconnected: int, provisioned: int, failed: int, dry_run: int, breaker: bool}
     */
    public function syncRouter(Mikrotik $router, bool $dryRun = false, bool $force = false): array
    {
        $summary = ['skipped' => false, 'checked' => 0, 'suspended' => 0, 'reconnected' => 0, 'provisioned' => 0, 'failed' => 0, 'dry_run' => 0, 'breaker' => false];
        $settings = MikrotikSetting::forSite($router->site_id);

        if (! $settings->is_enabled || ! $router->is_active) {
            return ['skipped' => true] + $summary;
        }

        $api = $this->connect($router);

        try {
            $secrets = $api->secrets();
            $sessions = collect($api->sessions())->groupBy('name')->map->all()->all();

            $clients = Client::withoutGlobalScopes()
                ->with('internetPlan')
                ->where('site_id', $router->site_id)
                ->where('mikrotik_id', $router->id)
                ->whereNotNull('pppoe_username')
                ->where(function ($query) {
                    // a deactivated client is revisited only until the removal has gone through
                    $query->where('is_active', 1)->orWhere('network_status', '!=', self::STATUS_INACTIVE);
                })
                ->get()
                ->filter(fn (Client $client) => $this->hasUsername($client));

            $today = $settings->today();
            $overdue = array_flip($this->policy->overdueClientIds($router->site_id, $router->id, $today));
            $isDryRun = $dryRun || $settings->dry_run;
            $allowSuspend = $force || $settings->withinDisconnectWindow();

            if ($isDryRun) {
                $this->dryRunLogged = array_flip(MikrotikLog::withoutGlobalScopes()
                    ->where('mikrotik_id', $router->id)
                    ->where('result', MikrotikLog::RESULT_DRY_RUN)
                    ->where('created_at', '>=', now()->subHours(20))
                    ->pluck('client_id')
                    ->all());
            }

            if ($allowSuspend && ! $force && ! $isDryRun && $this->breakerTrips($clients, $overdue, $secrets, $settings, $router)) {
                $allowSuspend = false;
                $summary['breaker'] = true;
            }

            $options = new SyncOptions(SyncOptions::TRIGGER_SWEEP, allowSuspend: $allowSuspend, dryRun: $isDryRun);

            foreach ($clients as $client) {
                $summary['checked']++;
                $secret = $secrets[$client->pppoe_username] ?? null;
                $action = $this->reconcile($client, $router, $api, $secret, $sessions, isset($overdue[$client->id]) && (bool) $client->is_active, $options);

                match ($action) {
                    'suspend' => $summary['suspended']++,
                    'reconnect' => $summary['reconnected']++,
                    'provision' => $summary['provisioned']++,
                    'failed' => $summary['failed']++,
                    'dry_run' => $summary['dry_run']++,
                    default => null,
                };
            }
        } finally {
            $api->close();
        }

        return $summary;
    }

    /** Remove a subscriber the app no longer tracks on this router (deleted, moved or renamed away). */
    public function removeSubscriber(int $routerId, int $siteId, string $username, ?int $clientId, string $trigger, ?int $actorId = null): void
    {
        $router = Mikrotik::withoutGlobalScopes()->where('site_id', $siteId)->where('id', $routerId)->where('is_active', 1)->first();

        if (! $router || ! MikrotikSetting::forSite($siteId)->is_enabled) {
            return;
        }

        $stillInUse = Client::withoutGlobalScopes()
            ->where('site_id', $siteId)
            ->where('mikrotik_id', $routerId)
            ->where('pppoe_username', $username)
            ->where('is_active', 1)
            ->when($clientId, fn ($query) => $query->where('id', '!=', $clientId))
            ->exists();

        if ($stillInUse) {
            return;
        }

        $api = $this->connect($router);

        try {
            $secret = $api->secret($username);

            if ($secret) {
                $api->removeSecret($secret['.id']);
                $this->dropSessions($api, $username, null);
                $this->log($router, $clientId, $username, 'remove', MikrotikLog::RESULT_SUCCESS, $trigger, null, $actorId);
            }
        } catch (RouterCommandException $ex) {
            $this->log($router, $clientId, $username, 'remove', MikrotikLog::RESULT_FAILED, $trigger, $ex->getMessage(), $actorId);
        } finally {
            $api->close();
        }
    }

    private function reconcile(Client $client, Mikrotik $router, RouterClient $api, ?array $secret, ?array $sessions, bool $overdue, SyncOptions $options): string
    {
        try {
            return $client->is_active
                ? $this->reconcileActive($client, $router, $api, $secret, $sessions, $overdue, $options)
                : $this->reconcileInactive($client, $router, $api, $secret, $sessions, $options);
        } catch (RouterCommandException $ex) {
            // the sweep retries hourly, so only a new problem earns a new log row
            if ($client->network_error !== $ex->getMessage()) {
                $this->mark($client, ['network_error' => $ex->getMessage()]);
                $this->log($router, $client->id, $client->pppoe_username, 'sync', MikrotikLog::RESULT_FAILED, $options->trigger, $ex->getMessage(), $options->actorId);
            }

            return 'failed';
        }
    }

    private function reconcileActive(Client $client, Mikrotik $router, RouterClient $api, ?array $secret, ?array $sessions, bool $overdue, SyncOptions $options): string
    {
        $username = $client->pppoe_username;
        $profile = $client->internetPlan?->mikrotik_profile;

        if (! $secret) {
            return $this->provision($client, $router, $api, $profile, $overdue, $options);
        }

        $isDisabled = ($secret['disabled'] ?? 'false') === 'true';
        $changes = [];
        $dropSession = false;
        $action = 'none';

        if ($secret['name'] !== $username) {
            $changes['name'] = $username;
            $dropSession = true;
            $action = 'update';
        }

        if ($options->syncPassword && $client->pppoe_password) {
            $changes['password'] = $client->pppoe_password;
            $dropSession = true;
            $action = 'update';
        }

        if ($profile && ($secret['profile'] ?? null) !== $profile) {
            $changes['profile'] = $profile;
            $dropSession = true;
            $action = 'update';
        }

        $status = $isDisabled ? self::STATUS_SUSPENDED : self::STATUS_ACTIVE;
        $note = null;

        if ($overdue && ! $isDisabled && $options->allowSuspend) {
            if ($options->dryRun) {
                if (! isset($this->dryRunLogged[$client->id])) {
                    $this->log($router, $client->id, $username, 'suspend', MikrotikLog::RESULT_DRY_RUN, $options->trigger, 'Would be disconnected: overdue bill.', $options->actorId);
                }
                $action = $action === 'none' ? 'dry_run' : $action;
            } else {
                $changes['disabled'] = 'yes';
                $dropSession = true;
                $status = self::STATUS_SUSPENDED;
                $action = 'suspend';
            }
        } elseif (! $overdue && $isDisabled) {
            $appCutThemOff = in_array($client->network_status, [self::STATUS_SUSPENDED, self::STATUS_INACTIVE], true);

            if ($appCutThemOff || $options->trigger === SyncOptions::TRIGGER_PAYMENT) {
                $changes['disabled'] = 'no';
                $status = self::STATUS_ACTIVE;
                $action = 'reconnect';
            } else {
                // switched off by hand on the router: not ours to undo on a routine pass
                $status = self::STATUS_DISABLED_EXTERNALLY;
                $note = 'Disabled on the router outside this app.';
            }
        }

        if ($changes !== []) {
            $api->updateSecret($secret['.id'], $changes);
        }

        if ($dropSession) {
            $this->dropSessions($api, $secret['name'], $sessions);
        }

        $this->mark($client, ['network_status' => $status, 'network_synced_at' => now(), 'network_error' => $note]);

        if ($changes !== []) {
            $this->log($router, $client->id, $username, $action, MikrotikLog::RESULT_SUCCESS, $options->trigger, null, $options->actorId);
        }

        return $action;
    }

    private function provision(Client $client, Mikrotik $router, RouterClient $api, ?string $profile, bool $overdue, SyncOptions $options): string
    {
        if (! $client->pppoe_password) {
            throw new RouterCommandException('Cannot create the subscriber on the router: no PPPoE password is saved.');
        }

        $suspend = $overdue && $options->allowSuspend && ! $options->dryRun;

        $attributes = [
            'name' => $client->pppoe_username,
            'password' => $client->pppoe_password,
            'service' => 'pppoe',
            'comment' => (string) $client->account_no,
            'disabled' => $suspend ? 'yes' : 'no',
        ];

        if ($profile) {
            $attributes['profile'] = $profile;
        }

        $api->addSecret($attributes);

        $this->mark($client, [
            'network_status' => $suspend ? self::STATUS_SUSPENDED : self::STATUS_ACTIVE,
            'network_synced_at' => now(),
            'network_error' => null,
        ]);
        $this->log($router, $client->id, $client->pppoe_username, 'provision', MikrotikLog::RESULT_SUCCESS, $options->trigger, null, $options->actorId);

        return 'provision';
    }

    /**
     * The client list's Delete button deactivates the client, so this is the
     * router side of a delete. The secret is removed when the app can rebuild
     * it on restore, and only disabled when the password exists solely on the router.
     */
    private function reconcileInactive(Client $client, Mikrotik $router, RouterClient $api, ?array $secret, ?array $sessions, SyncOptions $options): string
    {
        // never touch a secret this app has not synced: it may not be ours
        if (! $client->network_synced_at) {
            return 'none';
        }

        $username = $client->pppoe_username;
        $action = 'none';

        if ($secret) {
            if ($client->pppoe_password) {
                $api->removeSecret($secret['.id']);
                $action = 'remove';
            } elseif (($secret['disabled'] ?? 'false') !== 'true') {
                $api->updateSecret($secret['.id'], ['disabled' => 'yes']);
                $action = 'disable';
            }

            $this->dropSessions($api, $username, $sessions);
        }

        $this->mark($client, ['network_status' => self::STATUS_INACTIVE, 'network_synced_at' => now(), 'network_error' => null]);

        if ($action !== 'none') {
            $this->log($router, $client->id, $username, $action, MikrotikLog::RESULT_SUCCESS, $options->trigger, null, $options->actorId);
        }

        return $action;
    }

    /** A bad billing run looks like a mass disconnection, so refuse one that is abnormally large. */
    private function breakerTrips($clients, array $overdue, array $secrets, MikrotikSetting $settings, Mikrotik $router): bool
    {
        $active = $clients->filter(fn (Client $client) => (bool) $client->is_active);

        $cuts = $active->filter(function (Client $client) use ($overdue, $secrets) {
            $secret = $secrets[$client->pppoe_username] ?? null;

            return isset($overdue[$client->id]) && $secret && ($secret['disabled'] ?? 'false') !== 'true';
        })->count();

        $limit = max(self::BREAKER_MINIMUM, (int) ceil($active->count() * $settings->max_suspend_percent / 100));

        if ($cuts <= $limit) {
            return false;
        }

        $message = "Stopped: {$cuts} of {$active->count()} subscribers would be cut, the limit is {$limit}. Check the latest billing run.";
        $this->log($router, null, null, 'circuit_breaker', MikrotikLog::RESULT_FAILED, SyncOptions::TRIGGER_SWEEP, $message, null);
        DB::table('mikrotiks')->where('id', $router->id)->update(['last_error' => $message]);

        return true;
    }

    /** @param array<string, array<int, array<string, string>>>|null $sessions pre-fetched by name during a sweep */
    private function dropSessions(RouterClient $api, string $username, ?array $sessions): void
    {
        foreach ($sessions === null ? $api->sessions($username) : ($sessions[$username] ?? []) as $session) {
            try {
                $api->removeSession($session['.id']);
            } catch (RouterCommandException) {
                // the session ended between the lookup and the removal
            }
        }
    }

    private function routerFor(Client $client): ?Mikrotik
    {
        if (! $client->mikrotik_id) {
            return null;
        }

        return Mikrotik::withoutGlobalScopes()
            ->where('site_id', $client->site_id)
            ->where('id', $client->mikrotik_id)
            ->where('is_active', 1)
            ->first();
    }

    private function connect(Mikrotik $router): RouterClient
    {
        try {
            $api = $this->factory->forRouter($router);
        } catch (RouterConnectionException $ex) {
            DB::table('mikrotiks')->where('id', $router->id)->update(['last_checked_at' => now(), 'last_error' => $ex->getMessage()]);

            throw $ex;
        }

        DB::table('mikrotiks')->where('id', $router->id)->update(['last_seen_at' => now(), 'last_checked_at' => now(), 'last_error' => null]);

        return $api;
    }

    private function hasUsername(Client $client): bool
    {
        return trim((string) $client->pppoe_username) !== '';
    }

    /** Written straight to the table so a sync does not count as a staff edit. */
    private function mark(Client $client, array $attributes): void
    {
        DB::table('clients')->where('id', $client->id)->update($attributes);
    }

    private function log(Mikrotik $router, ?int $clientId, ?string $username, string $action, string $result, string $trigger, ?string $message, ?int $actorId): void
    {
        MikrotikLog::withoutGlobalScopes()->create([
            'site_id' => $router->site_id,
            'mikrotik_id' => $router->id,
            'client_id' => $clientId,
            'pppoe_username' => $username,
            'action' => $action,
            'result' => $result,
            'trigger' => $trigger,
            'message' => $message,
            'actor_id' => $actorId,
        ]);
    }
}
