<?php

use App\Libraries\Billing\Repair;
use App\Models\Billing;
use App\Models\Client;
use App\Models\Role;
use App\Models\Site;
use App\Models\SmsLog;
use App\Models\SmsSettings;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function smsSite(string $name): Site
{
    return Site::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_url' => Str::slug($name) . '.test',
        'company_name' => $name,
        'company_address' => 'Cebu',
        'company_email' => 'admin@' . Str::slug($name) . '.test',
        'company_phone' => '0900',
        'company_telephone' => '0900',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

function smsSettings(Site $site, array $overrides = []): SmsSettings
{
    return SmsSettings::forceCreate(array_merge([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'api_key' => 'key-' . $site->id,
        'sender_name' => 'SENDER' . $site->id,
        'is_enabled' => true,
        'test_mode' => false,
        'connection_timeout' => 5,
        'retry_attempts' => 0,
        'created_by' => 1,
        'updated_by' => 1,
    ], $overrides));
}

function smsClient(Site $site, ?string $mobile = '09171234567', array $overrides = []): Client
{
    return Client::forceCreate(array_merge([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'mobile_no' => (string) $mobile,
        'house_no' => '123 Rizal',
        'account_no' => (string) Str::uuid(),
        'installation_date' => '2024-08-29',
        'billing_category_id' => 1,
        'server_id' => 1,
        'internet_plan_id' => 1,
        'balance_from_prev_billing_status' => 'Billed',
        'prorate_fee_status' => 'Billed',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ], $overrides));
}

function smsBilling(Client $client, array $overrides = []): Billing
{
    return Billing::forceCreate(array_merge([
        'uuid' => (string) Str::uuid(),
        'site_id' => $client->site_id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-' . Str::random(6),
        'billing_type' => 1,
        'billing_date' => '2026-11-01 08:00:00',
        'billing_total' => 1000,
        'billing_balance' => 1000,
        'billing_status' => 'Pending',
        'billing_cutoff' => '2026-11-10',
        'due_date' => '2026-11-10',
        'disconnection_date' => '2026-11-20',
        'balance_from_prev_billing' => 0,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ], $overrides));
}

function createRepairBilling(Client $client): void
{
    app(BillingService::class)->generateBilling(new Repair, [
        'billingType' => '3',
        'clientId' => $client->id,
        'billingCutoff' => '2026-11-30',
        'disconnectionDate' => '2026-12-05',
        'billingItems' => [[
            'billingItemQuantity' => 1,
            'billingItemPrice' => 500,
            'billingItemAmount' => 500,
        ]],
    ]);
}

function fakeSemaphore(): void
{
    Http::fake(['*' => Http::response([['message_id' => 123, 'status' => 'Pending']])]);
}

beforeEach(function () {
    $this->site = smsSite('Alpha Net');

    $role = Role::forceCreate([
        'site_id' => $this->site->id,
        'name' => 'Admin',
        'description' => 'Admin',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->roleId = $role->id;

    Sanctum::actingAs(User::create([
        'site_id' => $this->site->id,
        'fullname' => 'Staff Member',
        'username' => 'staff',
        'email' => 'staff@alpha.test',
        'password' => 'secret-password',
        'role_id' => $role->id,
    ]));

    $this->travelTo('2026-11-15 10:00:00');
});

test('creating a billing texts the client through the site\'s own Semaphore account', function () {
    fakeSemaphore();
    smsSettings($this->site);
    $client = smsClient($this->site);

    createRepairBilling($client);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.semaphore.co/api/v4/messages'
        && $request['apikey'] === 'key-' . $this->site->id
        && $request['number'] === '639171234567'
        && $request['sendername'] === 'SENDER' . $this->site->id
        && str_contains($request['message'], 'PHP 500.00')
        && str_contains($request['message'], 'Due: Nov 30, 2026')
        && str_contains($request['message'], 'Disconnection: Dec 5, 2026'));

    $log = SmsLog::sole();
    expect($log->status)->toBe(SmsLog::STATUS_SENT)
        ->and($log->provider_message_id)->toBe('123')
        ->and($log->type)->toBe(SmsLog::TYPE_BILLING_CREATED);
});

test('clients without a usable contact number are skipped', function (string $mobile) {
    fakeSemaphore();
    smsSettings($this->site);

    createRepairBilling(smsClient($this->site, $mobile));

    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe(SmsLog::STATUS_SKIPPED)
        ->and(Billing::count())->toBe(1);
})->with(['', 'N/A', '12345']);

test('common number formats are normalized', function (string $raw) {
    expect(app(\App\Services\SmsNotificationService::class)->normalizeNumber($raw))->toBe('639171234567');
})->with(['09171234567', '+639171234567', '639171234567', '9171234567', '0917-123-4567', '0917 123 4567 / 09181112222']);

test('nothing is sent while SMS is disabled for the site', function () {
    fakeSemaphore();
    smsSettings($this->site, ['is_enabled' => false]);

    createRepairBilling(smsClient($this->site));

    Http::assertNothingSent();
    expect(SmsLog::count())->toBe(0);
});

test('test mode records the message without calling Semaphore', function () {
    fakeSemaphore();
    smsSettings($this->site, ['test_mode' => true]);

    createRepairBilling(smsClient($this->site));

    Http::assertNothingSent();
    expect(SmsLog::sole()->status)->toBe(SmsLog::STATUS_SIMULATED);
});

test('a Semaphore rejection is logged and does not fail the billing', function () {
    Http::fake(['*' => Http::response(['apikey' => ['The selected apikey is invalid.']], 200)]);
    smsSettings($this->site);

    createRepairBilling(smsClient($this->site));

    expect(Billing::count())->toBe(1)
        ->and(SmsLog::sole()->status)->toBe(SmsLog::STATUS_FAILED);
});

test('an outage does not fail the billing', function () {
    Http::fake(['*' => Http::response('down', 503)]);
    smsSettings($this->site);

    createRepairBilling(smsClient($this->site));

    expect(Billing::count())->toBe(1)
        ->and(SmsLog::sole()->status)->toBe(SmsLog::STATUS_FAILED);
});

test('overdue reminder covers the client once, with the full balance and disconnection date', function () {
    fakeSemaphore();
    smsSettings($this->site);
    $client = smsClient($this->site);
    smsBilling($client, ['due_date' => '2026-10-10', 'disconnection_date' => '2026-10-20', 'billing_balance' => 700]);
    smsBilling($client, ['billing_balance' => 1000]);

    $this->postJson('/api/sms/overdue-reminders')
        ->assertOk()
        ->assertJsonPath('data.queued', 1);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains($request['message'], 'PHP 1,700.00')
        && str_contains($request['message'], 'Nov 20, 2026'));

    $this->postJson('/api/sms/overdue-reminders')
        ->assertOk()
        ->assertJsonPath('data.already_sent', 1);

    Http::assertSentCount(1);
});

test('a separate reminder goes out on the disconnection date', function () {
    fakeSemaphore();
    smsSettings($this->site);
    smsBilling(smsClient($this->site), ['disconnection_date' => '2026-11-15']);

    $this->postJson('/api/sms/overdue-reminders')->assertOk();

    expect(SmsLog::sole()->type)->toBe(SmsLog::TYPE_DISCONNECTION_REMINDER);
    Http::assertSent(fn (Request $request) => str_contains($request['message'], 'disconnection today'));
});

test('billings that are not overdue get no reminder', function (array $billing) {
    fakeSemaphore();
    smsSettings($this->site);
    smsBilling(smsClient($this->site), $billing);

    $this->postJson('/api/sms/overdue-reminders')->assertOk();

    Http::assertNothingSent();
})->with([
    'not yet due' => [['due_date' => '2026-11-30', 'disconnection_date' => '2026-12-05']],
    'paid' => [['billing_status' => 'Paid']],
    'deactivated' => [['is_active' => 0]],
    'disconnection date passed' => [['due_date' => '2026-11-01', 'disconnection_date' => '2026-11-10']],
]);

test('a failed reminder is tried again on the next run', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['bad' => 'request'], 200)
        ->push([['message_id' => 9, 'status' => 'Pending']], 200)]);
    smsSettings($this->site);
    smsBilling(smsClient($this->site));

    $this->postJson('/api/sms/overdue-reminders')->assertOk();
    expect(SmsLog::sole()->status)->toBe(SmsLog::STATUS_FAILED);

    $this->postJson('/api/sms/overdue-reminders')->assertOk();
    expect(SmsLog::sole()->status)->toBe(SmsLog::STATUS_SENT);
});

test('the scheduled command uses each site\'s own credentials', function () {
    fakeSemaphore();
    $other = smsSite('Beta Net');
    smsSettings($this->site);
    smsSettings($other);
    smsBilling(smsClient($this->site, '09171111111'));
    smsBilling(smsClient($other, '09172222222'));

    auth()->forgetGuards();
    $this->artisan('app:send-overdue-sms')->assertSuccessful();

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r) => $r['number'] === '639171111111' && $r['apikey'] === 'key-' . $this->site->id);
    Http::assertSent(fn (Request $r) => $r['number'] === '639172222222' && $r['apikey'] === 'key-' . $other->id);
});

test('the overdue endpoint only touches the caller\'s site', function () {
    fakeSemaphore();
    $other = smsSite('Beta Net');
    smsSettings($this->site);
    smsSettings($other);
    smsBilling(smsClient($other));

    $this->postJson('/api/sms/overdue-reminders')->assertOk()->assertJsonPath('data.queued', 0);

    Http::assertNothingSent();
});

test('the overdue endpoint refuses when SMS is off', function () {
    smsSettings($this->site, ['is_enabled' => false]);

    $this->postJson('/api/sms/overdue-reminders')->assertStatus(422);
});

test('the API key is encrypted at rest and never returned', function () {
    $this->postJson('/api/smssettings', ['apiKey' => 'super-secret', 'isEnabled' => 1])->assertCreated();

    expect(DB::table('sms_settings')->value('api_key'))->not->toContain('super-secret')
        ->and(SmsSettings::first()->api_key)->toBe('super-secret');

    $response = $this->getJson('/api/smssettings')
        ->assertOk()
        ->assertJsonPath('data.attributes.hasApiKey', true);

    expect($response->getContent())->not->toContain('super-secret');
});

test('saving without a new key keeps the stored one', function () {
    smsSettings($this->site, ['api_key' => 'existing-key']);

    $this->postJson('/api/smssettings', ['senderName' => 'NEWNAME', 'isEnabled' => 1])->assertOk();

    expect(SmsSettings::first()->api_key)->toBe('existing-key')
        ->and(SmsSettings::first()->sender_name)->toBe('NEWNAME');
});

test('a site cannot write another site\'s settings', function () {
    $other = smsSite('Beta Net');

    $this->postJson('/api/smssettings', ['siteId' => $other->id, 'apiKey' => 'mine'])->assertCreated();

    expect(SmsSettings::withoutGlobalScopes()->where('site_id', $other->id)->count())->toBe(0)
        ->and(SmsSettings::withoutGlobalScopes()->where('site_id', $this->site->id)->count())->toBe(1);
});

test('SMS cannot be enabled without an API key', function () {
    $this->postJson('/api/smssettings', ['isEnabled' => 1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('apiKey');
});

test('the API URL must be a Semaphore address', function (string $url) {
    $this->postJson('/api/smssettings', ['apiUrl' => $url])
        ->assertStatus(422)
        ->assertJsonValidationErrors('apiUrl');
})->with(['http://api.semaphore.co/api/v4/messages', 'https://169.254.169.254/latest', 'https://evil.example/api']);

test('PUT and PATCH both update settings', function (string $method) {
    $settings = smsSettings($this->site);

    $this->json($method, "/api/smssettings/{$settings->uuid}", ['senderName' => 'CHANGED'])->assertOk();

    expect($settings->fresh()->sender_name)->toBe('CHANGED');
})->with(['PUT', 'PATCH']);

test('a test SMS sends immediately and never begins with TEST', function () {
    fakeSemaphore();
    $this->site->update(['company_name' => 'Test Fiber']);
    smsSettings($this->site);

    $this->postJson('/api/smssettings/test', ['number' => '09171234567'])
        ->assertOk()
        ->assertJsonPath('data.status', SmsLog::STATUS_SENT);

    Http::assertSent(fn (Request $request) => !str_starts_with(strtoupper($request['message']), 'TEST'));
});

test('an API URL with no path posts to the messages endpoint', function (string $url) {
    fakeSemaphore();
    smsSettings($this->site, ['api_url' => $url]);

    $this->postJson('/api/smssettings/test', ['number' => '09171234567'])->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.semaphore.co/api/v4/messages');
})->with(['https://api.semaphore.co', 'https://api.semaphore.co/']);

test('a test SMS is refused while SMS is disabled', function () {
    fakeSemaphore();
    smsSettings($this->site, ['is_enabled' => false]);

    $this->postJson('/api/smssettings/test', ['number' => '09171234567'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'SMS is turned off for this site. Turn on Enable SMS and save first.');

    Http::assertNothingSent();
    expect(SmsLog::count())->toBe(0);
});

test('a test SMS rejects an invalid number', function () {
    fakeSemaphore();
    smsSettings($this->site);

    $this->postJson('/api/smssettings/test', ['number' => '12345'])->assertStatus(422);

    Http::assertNothingSent();
});
