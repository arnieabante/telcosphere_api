<?php

use App\Jobs\ReconcileRouter;
use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\Internetplan;
use App\Models\Mikrotik;
use App\Models\MikrotikLog;
use App\Models\MikrotikSetting;
use App\Models\Role;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Services\Mikrotik\RouterClientFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakeRouter;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function netSite(string $name = 'a'): Site
{
    return Site::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_url' => "{$name}.test",
        'company_name' => $name,
        'company_address' => 'Cebu',
        'company_email' => "admin@{$name}.test",
        'company_phone' => '0900',
        'company_telephone' => '0900',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

function netStaff(Site $site): User
{
    if ($existing = User::where('username', "staff{$site->id}")->first()) {
        return $existing;
    }

    $role = Role::forceCreate(['site_id' => $site->id, 'name' => 'Admin', 'description' => 'Administrator', 'created_by' => 1, 'updated_by' => 1]);

    return User::create([
        'site_id' => $site->id,
        'fullname' => 'Staff',
        'username' => "staff{$site->id}",
        'email' => "staff{$site->id}@test.test",
        'password' => 'secret-password',
        'role_id' => $role->id,
        'is_active' => 1,
    ]);
}

function netRouter(Site $site, array $attributes = []): Mikrotik
{
    return Mikrotik::create([
        'site_id' => $site->id,
        'name' => 'Router '.Str::random(4),
        'ip_address' => '10.8.0.2',
        'username' => 'telcosphere',
        'password' => 'router-pass',
        ...$attributes,
    ]);
}

function netEnable(Site $site, array $attributes = []): void
{
    MikrotikSetting::create([
        'site_id' => $site->id,
        'is_enabled' => true,
        'dry_run' => false,
        'disconnect_window_start' => '00:00',
        'disconnect_window_end' => '23:59',
        ...$attributes,
    ]);
}

function netPlan(Site $site, ?string $profile = 'PLAN_10M'): Internetplan
{
    return Internetplan::forceCreate(['uuid' => (string) Str::uuid(), 'site_id' => $site->id, 'name' => 'Plan '.Str::random(4), 'mikrotik_profile' => $profile, 'monthly_subscription' => 1000, 'is_featured' => false, 'features' => ['x'], 'created_by' => 1, 'updated_by' => 1]);
}

function netClient(Site $site, ?Mikrotik $router, array $attributes = []): Client
{
    static $n = 0;
    $n++;

    // the model resets these on save, so they are applied afterwards
    $network = array_intersect_key($attributes, array_flip(['network_status', 'network_synced_at']));
    $attributes = array_diff_key($attributes, $network);

    $client = Client::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'first_name' => 'Juan',
        'last_name' => "Cruz{$n}",
        'mobile_no' => '09170000000',
        'house_no' => 'House 1',
        'account_no' => "ACC{$n}",
        'installation_date' => '2026-01-01',
        'billing_category_id' => 1,
        'server_id' => 1,
        'internet_plan_id' => $attributes['internet_plan_id'] ?? netPlan($site)->id,
        'pppoe_username' => "user{$n}",
        'pppoe_password' => 'ppp-secret',
        'mikrotik_id' => $router?->id,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
        ...$attributes,
    ]);

    if ($network !== []) {
        DB::table('clients')->where('id', $client->id)->update($network);
    }

    return $client->fresh();
}

function netBill(Client $client, array $attributes = []): Billing
{
    // the model stamps created_by from the site's administrator
    netStaff(Site::find($client->site_id));

    return Billing::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $client->site_id,
        'client_id' => $client->id,
        'invoice_number' => (string) Str::uuid(),
        'billing_type' => 1,
        'billing_date' => now()->subMonth(),
        'billing_total' => 1000,
        'billing_offset' => 0,
        'billing_balance' => 1000,
        'billing_status' => 'Pending',
        'disconnection_date' => now()->subDay()->toDateString(),
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
        ...$attributes,
    ]);
}

function netSweep(Mikrotik $router, bool $dryRun = false): array
{
    return app(\App\Services\Mikrotik\SubscriberSyncService::class)->syncRouter($router->fresh(), $dryRun);
}

beforeEach(function () {
    $this->router = new FakeRouter;
    $this->app->instance(RouterClientFactory::class, $this->router);
    $this->site = netSite();
    netEnable($this->site);
    $this->mikrotik = netRouter($this->site);
    Carbon::setTestNow('2026-11-09 10:00:00');
});

afterEach(fn () => Carbon::setTestNow());

// ---------- Goal 1: add / update / delete

it('creates the subscriber on the router when a client is added', function () {
    Sanctum::actingAs(netStaff($this->site));
    $plan = netPlan($this->site);

    $this->postJson('/api/clients', [
        'firstName' => 'Juan', 'lastName' => 'Dela Cruz', 'mobileNo' => '09170000000', 'houseNo' => 'House 12',
        'accountNo' => 'A-100', 'installationDate' => '2026-01-01', 'prorateFee' => 0, 'inactiveDate' => '2030-01-01',
        'billingCategoryId' => 1, 'serverId' => 1, 'internetPlanId' => $plan->id, 'isActive' => '1',
        'PPPOEUsername' => 'juan01', 'PPPOEPassword' => 'pw-123', 'mikrotikId' => $this->mikrotik->id,
    ])->assertCreated();

    $secret = $this->router->byName('juan01');

    expect($secret)->not->toBeNull()
        ->and($secret['password'])->toBe('pw-123')
        ->and($secret['profile'])->toBe('PLAN_10M')
        ->and($secret['service'])->toBe('pppoe')
        ->and($secret['comment'])->toBe('A-100')
        ->and(Client::where('pppoe_username', 'juan01')->first()->network_status)->toBe('active');
});

it('adopts an existing secret without overwriting its password', function () {
    $this->router->seedSecret('existing', ['password' => 'router-original']);
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, null, ['pppoe_username' => 'existing']);

    $this->patchJson("/api/clients/{$client->uuid}", ['mikrotikId' => $this->mikrotik->id])->assertOk();

    expect($this->router->byName('existing')['password'])->toBe('router-original')
        ->and($client->fresh()->network_status)->toBe('active');
});

it('applies a password change and drops the live session', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan', ['password' => 'ppp-secret'], online: true);

    $this->patchJson("/api/clients/{$client->uuid}", ['PPPOEPassword' => 'new-pass'])->assertOk();

    expect($this->router->byName('juan')['password'])->toBe('new-pass')
        ->and($this->router->isOnline('juan'))->toBeFalse();
});

it('keeps the password when the form leaves it blank and leaves the live session alone', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan', ['profile' => 'PLAN_10M', 'password' => 'ppp-secret'], online: true);

    $this->patchJson("/api/clients/{$client->uuid}", ['PPPOEPassword' => '', 'notes' => 'updated'])->assertOk();

    expect($client->fresh()->pppoe_password)->toBe('ppp-secret')
        ->and($this->router->isOnline('juan'))->toBeTrue()
        ->and($this->router->writes)->toBe([]);
});

it('renames the secret instead of creating a second one', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'old', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('old');

    $this->patchJson("/api/clients/{$client->uuid}", ['PPPOEUsername' => 'new'])->assertOk();

    expect($this->router->byName('old'))->toBeNull()
        ->and($this->router->byName('new'))->not->toBeNull()
        ->and($this->router->store)->toHaveCount(1);
});

it('changes the profile and drops the session when the plan changes', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan', ['profile' => 'PLAN_10M'], online: true);
    $faster = netPlan($this->site, 'PLAN_50M');

    $this->patchJson("/api/clients/{$client->uuid}", ['internetPlanId' => $faster->id])->assertOk();

    expect($this->router->byName('juan')['profile'])->toBe('PLAN_50M')
        ->and($this->router->isOnline('juan'))->toBeFalse();
});

it('removes the secret when the list Delete button deactivates the client, and restores it', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan', ['profile' => 'PLAN_10M'], online: true);

    $this->patchJson("/api/clients/{$client->uuid}", ['isActive' => 0])->assertOk();

    expect($this->router->byName('juan'))->toBeNull()
        ->and($this->router->isOnline('juan'))->toBeFalse()
        ->and($client->fresh()->network_status)->toBe('inactive');

    $this->patchJson("/api/clients/{$client->uuid}", ['isActive' => 1])->assertOk();

    expect($this->router->byName('juan')['password'])->toBe('ppp-secret')
        ->and($this->router->byName('juan')['disabled'])->toBe('false');
});

it('only disables on deactivation when the password exists solely on the router', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'pppoe_password' => null, 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan');

    $this->patchJson("/api/clients/{$client->uuid}", ['isActive' => 0])->assertOk();

    expect($this->router->byName('juan')['disabled'])->toBe('true');
});

it('deletes the secret on a hard delete', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan', [], online: true);

    $this->deleteJson("/api/clients/{$client->uuid}")->assertOk();

    expect($this->router->byName('juan'))->toBeNull()
        ->and($this->router->isOnline('juan'))->toBeFalse();
});

it('never deletes a secret the app has not synced', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'theirs']);
    DB::table('clients')->where('id', $client->id)->update(['network_synced_at' => null]);
    $this->router->seedSecret('theirs');

    $this->deleteJson("/api/clients/{$client->uuid}")->assertOk();

    expect($this->router->byName('theirs'))->not->toBeNull();
});

it('moves the subscriber when the client changes router', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan', 'network_status' => 'active', 'network_synced_at' => now()]);
    $this->router->seedSecret('juan');
    $other = netRouter($this->site);

    Queue::fake();
    $this->patchJson("/api/clients/{$client->uuid}", ['mikrotikId' => $other->id])->assertOk();

    Queue::assertPushed(\App\Jobs\RemoveRouterSubscriber::class, fn ($job) => $job->routerId === $this->mikrotik->id && $job->username === 'juan');
    Queue::assertPushed(\App\Jobs\ReconcileClient::class, fn ($job) => $job->routerId === $other->id);
});

it('saves the client even when the router is offline and records why', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan']);
    $this->router->offline = true;

    // the sync queue runs inline here, so the retryable failure surfaces in the job
    config(['queue.default' => 'database']);
    $this->patchJson("/api/clients/{$client->uuid}", ['notes' => 'still saved'])->assertOk();

    expect($client->fresh()->notes)->toBe('still saved')
        ->and(DB::table('jobs')->count())->toBe(1);
});

it('records a refused command on the client instead of crashing', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'juan']);
    $this->router->refuse = 'input does not match any value of profile';

    $summary = netSweep($this->mikrotik);

    expect($summary['failed'])->toBe(1)
        ->and($client->fresh()->network_error)->toContain('profile');
});

// ---------- Goal 2: the sweep

it('disconnects an overdue subscriber and kills the live session', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'active']);
    netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M'], online: true);

    $summary = netSweep($this->mikrotik);

    expect($summary['suspended'])->toBe(1)
        ->and($this->router->byName('late')['disabled'])->toBe('true')
        ->and($this->router->isOnline('late'))->toBeFalse()
        ->and($client->fresh()->network_status)->toBe('suspended')
        ->and(MikrotikLog::where('action', 'suspend')->where('result', 'success')->count())->toBe(1);
});

it('cuts on the disconnection date in the company timezone', function () {
    // 2026-11-09 18:00 UTC is already the 10th in Manila
    Carbon::setTestNow('2026-11-09 18:00:00');
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    netBill($client, ['disconnection_date' => '2026-11-10']);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M']);
    MikrotikSetting::where('site_id', $this->site->id)->update(['disconnect_window_start' => '00:00', 'disconnect_window_end' => '23:59']);

    expect(netSweep($this->mikrotik)['suspended'])->toBe(1);
});

it('leaves a client alone when the disconnection date is in the future', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'ok']);
    netBill($client, ['disconnection_date' => now()->addDays(3)->toDateString()]);
    $this->router->seedSecret('ok', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['suspended'])->toBe(0);
});

it('ignores paid bills, voided bills and bills with no disconnection date', function (array $bill) {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'ok']);
    netBill($client, $bill);
    $this->router->seedSecret('ok', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['suspended'])->toBe(0);
})->with([
    'paid' => [['billing_status' => 'Paid', 'billing_balance' => 0]],
    'voided' => [['is_active' => 0]],
    'repair bill with no date' => [['disconnection_date' => null]],
]);

it('still cuts for an older unpaid bill when the newest one is paid', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    netBill($client);
    netBill($client, ['billing_status' => 'Paid', 'billing_balance' => 0, 'disconnection_date' => now()->addMonth()->toDateString()]);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['suspended'])->toBe(1);
});

it('honours a staff exemption', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'promised', 'suspension_exempt_until' => now()->addDay()->toDateString()]);
    netBill($client);
    $this->router->seedSecret('promised', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['suspended'])->toBe(0);
});

it('only reports in dry-run, once a day', function () {
    MikrotikSetting::where('site_id', $this->site->id)->update(['dry_run' => true]);
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M'], online: true);

    netSweep($this->mikrotik);
    netSweep($this->mikrotik);

    expect($this->router->byName('late')['disabled'])->toBe('false')
        ->and($this->router->isOnline('late'))->toBeTrue()
        ->and(MikrotikLog::where('result', 'dry_run')->count())->toBe(1);
});

it('does not cut outside the window but still reconnects', function () {
    MikrotikSetting::where('site_id', $this->site->id)->update(['disconnect_window_start' => '01:00', 'disconnect_window_end' => '02:00']);
    $late = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    netBill($late);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M']);
    netClient($this->site, $this->mikrotik, ['pppoe_username' => 'paid', 'network_status' => 'suspended']);
    $this->router->seedSecret('paid', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    $summary = netSweep($this->mikrotik);

    expect($summary['suspended'])->toBe(0)
        ->and($summary['reconnected'])->toBe(1)
        ->and($this->router->byName('paid')['disabled'])->toBe('false');
});

it('trips the circuit breaker on an abnormal run but keeps reconnecting', function () {
    foreach (range(1, 20) as $i) {
        $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => "u{$i}"]);
        $this->router->seedSecret("u{$i}", ['profile' => 'PLAN_10M']);

        if ($i <= 10) {
            netBill($client);
        }
    }

    netClient($this->site, $this->mikrotik, ['pppoe_username' => 'paid', 'network_status' => 'suspended']);
    $this->router->seedSecret('paid', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    $summary = netSweep($this->mikrotik);

    expect($summary['breaker'])->toBeTrue()
        ->and($summary['suspended'])->toBe(0)
        ->and($summary['reconnected'])->toBe(1)
        ->and($this->mikrotik->fresh()->last_error)->toContain('Stopped');
});

it('does not trip the breaker on a small router', function () {
    foreach (range(1, 4) as $i) {
        $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => "u{$i}"]);
        netBill($client);
        $this->router->seedSecret("u{$i}", ['profile' => 'PLAN_10M']);
    }

    expect(netSweep($this->mikrotik)['suspended'])->toBe(4);
});

it('does nothing with the integration switched off', function () {
    MikrotikSetting::where('site_id', $this->site->id)->update(['is_enabled' => false]);
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['skipped'])->toBeTrue()
        ->and($this->router->writes)->toBe([]);
});

it('defaults a company to off and dry-run', function () {
    $settings = MikrotikSetting::forSite(netSite('new')->id);

    expect($settings->is_enabled)->toBeFalse()->and($settings->dry_run)->toBeTrue();
});

it('queues one job per enabled router across companies, and none for a disabled one', function () {
    $other = netSite('b');
    netEnable($other);
    $otherRouter = netRouter($other);
    $off = netSite('c');
    netRouter($off);

    Queue::fake();
    $this->artisan('mikrotik:sweep')->assertSuccessful();

    Queue::assertPushed(ReconcileRouter::class, 2);
    Queue::assertPushed(ReconcileRouter::class, fn ($job) => $job->routerId === $otherRouter->id);
});

it('only touches clients of the router being swept', function () {
    $other = netSite('b');
    netEnable($other);
    $foreign = netClient($other, netRouter($other), ['pppoe_username' => 'same']);
    netBill($foreign);
    $this->router->seedSecret('same', ['profile' => 'PLAN_10M']);

    expect(netSweep($this->mikrotik)['checked'])->toBe(0)
        ->and($this->router->byName('same')['disabled'])->toBe('false');
});

it('leaves a secret disabled by hand on a routine pass', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'manual', 'network_status' => 'active']);
    $this->router->seedSecret('manual', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    netSweep($this->mikrotik);

    expect($this->router->byName('manual')['disabled'])->toBe('true')
        ->and($client->fresh()->network_status)->toBe('disabled_externally');
});

it('schedules the sweep', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('mikrotik:sweep')->assertSuccessful();
});

// ---------- Goal 3: payments reconnect

function netSettle(Billing $bill): void
{
    DB::table('billings')->where('id', $bill->id)->update(['billing_status' => 'Paid', 'billing_balance' => 0]);
}

/** A real POST /api/payments for the whole of a bill, or part of it. */
function netCollect($test, Client $client, Billing $bill, string $amount)
{
    // the controller uses MySQL's GREATEST, which the sqlite test database lacks
    DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));

    $item = \App\Models\BillingItem::forceCreate([
        'uuid' => (string) Str::uuid(), 'site_id' => $client->site_id, 'billing_id' => $bill->id,
        'billing_item_name' => 'Fee', 'billing_item_particulars' => 'Fee', 'billing_item_quantity' => 1,
        'billing_item_price' => 1000, 'billing_item_amount' => 1000,
        'billing_item_offset' => 0, 'billing_item_balance' => 1000, 'billing_status' => 'Pending',
        'is_active' => 1, 'created_by' => 1, 'updated_by' => 1,
    ]);

    return $test->postJson('/api/payments', [
        'clientId' => $client->id, 'collectionDate' => '2026-11-09', 'paymentMethod' => 'Cash',
        'subtotal' => $amount, 'discount' => '0.00', 'total' => $amount, 'amountReceived' => $amount,
        'amountChange' => '0.00', 'amountPaid' => $amount, 'balance' => '0.00', 'isActive' => '1',
        'collectionItems' => [[
            'billing_item_id' => $item->id, 'particulars' => 'Fee', 'amount' => 1000,
            'amount_paid' => (float) $amount, 'amount_balance' => 1000 - (float) $amount,
        ]],
    ]);
}

it('reconnects when a collection pays the overdue bill in full', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'suspended']);
    $bill = netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    netCollect($this, $client, $bill, '1000.00')->assertCreated();

    expect($bill->fresh()->billing_status)->toBe('Paid')
        ->and($this->router->byName('late')['disabled'])->toBe('false')
        ->and($client->fresh()->network_status)->toBe('active')
        ->and(MikrotikLog::where('action', 'reconnect')->where('trigger', 'payment')->count())->toBe(1);
});

it('stays cut off when a collection only partly pays the overdue bill', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'suspended']);
    $bill = netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    netCollect($this, $client, $bill, '400.00')->assertCreated();

    expect($bill->fresh()->billing_status)->toBe('Partial')
        ->and($this->router->byName('late')['disabled'])->toBe('true');
});

it('does not fail a collection when the router is offline', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'suspended']);
    $bill = netBill($client);
    $this->router->offline = true;

    netCollect($this, $client, $bill, '1000.00')->assertCreated();

    expect($bill->fresh()->billing_status)->toBe('Paid');
});

it('reconnects as soon as the overdue bill is settled', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'suspended']);
    $bill = netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    netSettle($bill);
    \App\Services\Mikrotik\NetworkEvents::requestSync($client->id, 'payment');

    expect($this->router->byName('late')['disabled'])->toBe('false')
        ->and($client->fresh()->network_status)->toBe('active')
        ->and(MikrotikLog::where('action', 'reconnect')->where('trigger', 'payment')->count())->toBe(1);
});

it('stays cut off after a partial payment', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'suspended']);
    $bill = netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    DB::table('billings')->where('id', $bill->id)->update(['billing_status' => 'Partial', 'billing_balance' => 400]);
    \App\Services\Mikrotik\NetworkEvents::requestSync($client->id, 'payment');

    expect($this->router->byName('late')['disabled'])->toBe('true');
});

it('reconnects a client who was disabled by hand once they pay', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'manual', 'network_status' => 'disabled_externally']);
    $this->router->seedSecret('manual', ['profile' => 'PLAN_10M', 'disabled' => 'true']);

    \App\Services\Mikrotik\NetworkEvents::requestSync($client->id, 'payment');

    expect($this->router->byName('manual')['disabled'])->toBe('false');
});

it('never cuts anyone off from a payment event', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late', 'network_status' => 'active']);
    netBill($client);
    $this->router->seedSecret('late', ['profile' => 'PLAN_10M'], online: true);

    \App\Services\Mikrotik\NetworkEvents::requestSync($client->id, 'payment');

    expect($this->router->byName('late')['disabled'])->toBe('false')
        ->and($this->router->isOnline('late'))->toBeTrue();
});

it('queues the reconnect only after the database commits', function () {
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'late']);
    Queue::fake();

    DB::beginTransaction();
    \App\Services\Mikrotik\NetworkEvents::requestSync($client->id, 'payment');
    Queue::assertNothingPushed();
    DB::rollBack();

    Queue::assertNothingPushed();
});

// ---------- Goal 4: configuration, security and billing fixes

it('actually saves a router and never returns its password', function () {
    Sanctum::actingAs(netStaff($this->site));

    $response = $this->postJson('/api/mikrotiks', ['name' => 'Main', 'ipAddress' => '10.8.0.9', 'username' => 'api', 'password' => 'top-secret', 'isActive' => 1]);

    $response->assertCreated()->assertJsonMissingPath('data.attributes.password');
    expect($response->getContent())->not->toContain('top-secret');

    $raw = DB::table('mikrotiks')->where('name', 'Main')->value('password');
    expect($raw)->not->toBe('top-secret')
        ->and(Mikrotik::where('name', 'Main')->first()->password)->toBe('top-secret');
});

it('ignores a forged site_id header when creating a router', function () {
    $victim = netSite('victim');
    Sanctum::actingAs(netStaff($this->site));

    $this->withHeader('site_id', (string) $victim->id)
        ->postJson('/api/mikrotiks', ['name' => 'Sneaky', 'ipAddress' => '10.8.0.9', 'username' => 'api', 'password' => 'x', 'isActive' => 1])
        ->assertCreated();

    expect(Mikrotik::withoutGlobalScopes()->where('name', 'Sneaky')->value('site_id'))->toBe($this->site->id);
});

it('keeps the router password when an edit leaves it blank', function () {
    Sanctum::actingAs(netStaff($this->site));

    $this->patchJson("/api/mikrotiks/{$this->mikrotik->uuid}", ['name' => $this->mikrotik->name, 'password' => ''])->assertOk();

    expect($this->mikrotik->fresh()->password)->toBe('router-pass');
});

it('blocks deleting a router that still has clients', function () {
    Sanctum::actingAs(netStaff($this->site));
    netClient($this->site, $this->mikrotik);

    $this->patchJson("/api/mikrotiks/{$this->mikrotik->uuid}", ['isActive' => 0])->assertStatus(409);
    expect($this->mikrotik->fresh()->is_active)->toBeTrue();
});

it('tests a connection and reports failure', function () {
    Sanctum::actingAs(netStaff($this->site));
    $payload = ['ipAddress' => '10.8.0.9', 'username' => 'api', 'password' => 'x'];

    $this->postJson('/api/mikrotiks/test-connection', $payload)->assertOk()->assertJsonPath('data.identity', 'fake');

    $this->router->offline = true;
    $this->postJson('/api/mikrotiks/test-connection', $payload)->assertStatus(422);
});

it('refuses the app host and metadata addresses as routers', function (string $host) {
    (new RouterClientFactory)->connect($host, 8728, false, 'a', 'b', 1);
})->with(['127.0.0.1', '169.254.169.254', 'localhost'])->throws(\App\Services\Mikrotik\RouterConnectionException::class, 'not allowed');

it('does not expose PPPoE passwords, and stores them encrypted', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, $this->mikrotik, ['pppoe_password' => 'hidden-pass']);
    // the statement of account uses MySQL's DATE_FORMAT, which the sqlite test database lacks
    DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date) => substr((string) $date, 0, 10));
    DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));

    $show = $this->getJson("/api/clients/{$client->uuid}")->assertOk();
    $soa = $this->getJson("/api/clients/{$client->uuid}/soa")->assertOk();

    expect($show->getContent())->not->toContain('hidden-pass')
        ->and($soa->getContent())->not->toContain('hidden-pass')
        ->and($show->json('data.attributes.hasPPPOEPassword'))->toBeTrue()
        ->and(DB::table('clients')->where('id', $client->id)->value('pppoe_password'))->not->toBe('hidden-pass');
});

it('rejects a router from another company', function () {
    $other = netSite('b');
    $foreign = netRouter($other);
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, null);

    $this->patchJson("/api/clients/{$client->uuid}", ['mikrotikId' => $foreign->id])->assertJsonValidationErrors('mikrotikId');
});

it('rejects a router serving a different area', function () {
    Sanctum::actingAs(netStaff($this->site));
    $area = netRouter($this->site, ['server_id' => 7]);
    $client = netClient($this->site, null, ['server_id' => 8]);

    $this->patchJson("/api/clients/{$client->uuid}", ['mikrotikId' => $area->id])->assertJsonValidationErrors('mikrotikId');
});

it('rejects a duplicate PPPoE username on one router', function () {
    Sanctum::actingAs(netStaff($this->site));
    netClient($this->site, $this->mikrotik, ['pppoe_username' => 'taken']);
    $client = netClient($this->site, $this->mikrotik, ['pppoe_username' => 'free']);

    $this->patchJson("/api/clients/{$client->uuid}", ['PPPOEUsername' => 'taken'])->assertJsonValidationErrors('PPPOEUsername');
});

it('lets two companies use the same subscriber email', function () {
    $other = netSite('b');
    netClient($other, null, ['email' => 'same@test.test']);
    Sanctum::actingAs(netStaff($this->site));

    $response = $this->postJson('/api/clients', [
        'firstName' => 'Juan', 'lastName' => 'Cruz', 'mobileNo' => '09170000000', 'houseNo' => 'House 12', 'email' => 'same@test.test',
        'accountNo' => 'A-200', 'installationDate' => '2026-01-01', 'prorateFee' => 0, 'inactiveDate' => '2030-01-01',
        'billingCategoryId' => 1, 'serverId' => 1, 'internetPlanId' => netPlan($this->site)->id, 'isActive' => '1',
    ]);

    expect($response->json('errors.email'))->toBeNull();
});

it('stores settings for the company', function () {
    Sanctum::actingAs(netStaff($this->site));

    $this->putJson('/api/mikrotik-settings', [
        'isEnabled' => true, 'dryRun' => false, 'disconnectWindowStart' => '09:00',
        'disconnectWindowEnd' => '16:00', 'maxSuspendPercent' => 20, 'timezone' => 'Asia/Manila',
    ])->assertOk()->assertJsonPath('data.maxSuspendPercent', 20);

    $this->getJson('/api/mikrotik-settings')->assertJsonPath('data.disconnectWindowStart', '09:00');
});

it('imports matches, reports the rest, and writes nothing without --apply', function () {
    $client = netClient($this->site, null, ['pppoe_username' => 'match']);
    $this->router->seedSecret('match');
    $this->router->seedSecret('stranger');

    $this->artisan('mikrotik:import', ['router' => $this->mikrotik->id])
        ->expectsOutputToContain('1 matched')
        ->assertSuccessful();
    expect($client->fresh()->mikrotik_id)->toBeNull();

    $this->artisan('mikrotik:import', ['router' => $this->mikrotik->id, '--apply' => true])->assertSuccessful();
    expect((int) $client->fresh()->mikrotik_id)->toBe($this->mikrotik->id)
        ->and($client->fresh()->network_status)->toBe('active')
        ->and($this->router->writes)->toBe([]);
});

function netCategory(Site $site, array $attributes = []): BillingCategory
{
    return BillingCategory::forceCreate($attributes + [
        'uuid' => (string) Str::uuid(), 'site_id' => $site->id, 'name' => 'Cycle '.Str::random(4), 'description' => 'x',
        'date_cycle' => 9, 'days_to_due_date' => 5, 'days_to_disconnection_date' => 3, 'is_active' => 1, 'created_by' => 1, 'updated_by' => 1,
    ]);
}

it('runs automated billing with nobody logged in, for the right company, with a disconnection date', function () {
    $other = netSite('b');
    netStaff($other);
    $category = netCategory($other);
    $plan = netPlan($other);
    $client = netClient($other, null, ['billing_category_id' => $category->id, 'internet_plan_id' => $plan->id, 'installation_date' => '2025-01-01', 'balance_from_prev_billing_status' => 'Billed']);

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    $bill = Billing::withoutGlobalScopes()->where('client_id', $client->id)->first();
    expect($bill)->not->toBeNull()
        ->and((int) $bill->site_id)->toBe($other->id)
        ->and((string) $bill->disconnection_date)->toContain('2026-11-17')
        ->and((int) DB::table('billing_items')->where('billing_id', $bill->id)->value('site_id'))->toBe($other->id);
});

it('does not bill a client twice in a month, even when the first bill is paid', function () {
    netStaff($this->site);
    $category = netCategory($this->site);
    $client = netClient($this->site, null, ['billing_category_id' => $category->id, 'installation_date' => '2025-01-01', 'balance_from_prev_billing_status' => 'Billed']);
    netBill($client, ['billing_date' => now()->startOfMonth()->addDay(), 'billing_status' => 'Paid', 'billing_balance' => 0]);

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::withoutGlobalScopes()->where('client_id', $client->id)->count())->toBe(1);
});

it('bills a cycle day the month does not have on the last day', function () {
    Carbon::setTestNow('2026-11-30 10:00:00');
    netStaff($this->site);
    $category = netCategory($this->site, ['date_cycle' => 31]);
    $client = netClient($this->site, null, ['billing_category_id' => $category->id, 'installation_date' => '2025-01-01', 'balance_from_prev_billing_status' => 'Billed']);

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::withoutGlobalScopes()->where('client_id', $client->id)->count())->toBe(1);
});

it('leaves automated billing off in the schedule by default', function () {
    expect(config('services.billing.automated_enabled'))->toBeFalsy();
});

it('shows overdue bills on the dashboard', function () {
    Sanctum::actingAs(netStaff($this->site));
    $client = netClient($this->site, null);
    netBill($client);
    netBill($client, ['disconnection_date' => now()->addDays(5)->toDateString()]);

    expect(app(\App\Services\DashboardService::class)->getOverdueBillings())->toHaveCount(1);
});
