<?php

use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\Internetplan;
use App\Models\Role;
use App\Models\Site;
use App\Models\SmsLog;
use App\Models\SmsSettings;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function autoBillingSite(string $name, bool $withAdministrator = true): Site
{
    $site = Site::forceCreate([
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

    if ($withAdministrator) {
        $role = Role::forceCreate([
            'site_id' => $site->id,
            'name' => 'Admin ' . $name,
            'description' => 'Administrator',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        User::create([
            'site_id' => $site->id,
            'fullname' => 'Admin ' . $name,
            'username' => 'admin-' . Str::slug($name),
            'email' => 'staff@' . Str::slug($name) . '.test',
            'password' => 'secret-password',
            'role_id' => $role->id,
        ]);
    }

    return $site;
}

function autoBillingCategory(Site $site, string $name, int $cycle = 15): BillingCategory
{
    return BillingCategory::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'name' => $name,
        'date_cycle' => $cycle,
        'days_to_due_date' => 5,
        'days_to_disconnection_date' => 3,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

function autoBillingClient(Site $site, BillingCategory $category): Client
{
    $plan = Internetplan::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'name' => 'PLAN ' . Str::random(6),
        'monthly_subscription' => '1000',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    return Client::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'mobile_no' => '09171234567',
        'house_no' => '123 Rizal',
        'account_no' => (string) Str::uuid(),
        'installation_date' => '2024-08-29',
        'billing_category_id' => $category->id,
        'server_id' => 1,
        'internet_plan_id' => $plan->id,
        'balance_from_prev_billing_status' => 'Billed',
        'prorate_fee_status' => 'Billed',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

beforeEach(function () {
    $this->travelTo('2026-11-15 00:00:00');
});

test('the scheduled command bills a site with nobody logged in', function () {
    $site = autoBillingSite('Alpha Net');
    $client = autoBillingClient($site, autoBillingCategory($site, 'Cycle 15'));
    $adminId = User::where('site_id', $site->id)->value('id');

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    $billing = Billing::sole();

    expect($billing->client_id)->toBe($client->id)
        ->and((int) $billing->site_id)->toBe($site->id)
        ->and((int) $billing->created_by)->toBe($adminId)
        ->and($billing->billingItems()->count())->toBe(1)
        ->and(Str::of($billing->due_date)->substr(0, 10)->toString())->toBe('2026-11-20')
        ->and(Str::of($billing->disconnection_date)->substr(0, 10)->toString())->toBe('2026-11-23')
        ->and((float) $billing->billing_balance)->toBe(1000.0)
        ->and(auth()->check())->toBeFalse();
});

test('each company is billed under its own site', function () {
    $alpha = autoBillingSite('Alpha Net');
    $beta = autoBillingSite('Beta Net');
    $alphaClient = autoBillingClient($alpha, autoBillingCategory($alpha, 'Alpha 15'));
    $betaClient = autoBillingClient($beta, autoBillingCategory($beta, 'Beta 15'));

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect((int) Billing::where('client_id', $alphaClient->id)->sole()->site_id)->toBe($alpha->id)
        ->and((int) Billing::where('client_id', $betaClient->id)->sole()->site_id)->toBe($beta->id);
});

test('only categories whose cycle is today are billed', function () {
    $site = autoBillingSite('Alpha Net');
    autoBillingClient($site, autoBillingCategory($site, 'Cycle 20', 20));

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::count())->toBe(0);
});

test('a category with nobody to bill does not stop the next one', function () {
    $site = autoBillingSite('Alpha Net');
    autoBillingCategory($site, 'Empty');
    $client = autoBillingClient($site, autoBillingCategory($site, 'Has Clients'));

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::where('client_id', $client->id)->count())->toBe(1);
});

test('running twice does not bill the same cycle twice', function () {
    $site = autoBillingSite('Alpha Net');
    autoBillingClient($site, autoBillingCategory($site, 'Cycle 15'));

    $this->artisan('app:run-automated-billing')->assertSuccessful();
    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::count())->toBe(1);
});

test('a cycle on a day the month does not have is billed on its last day', function () {
    $this->travelTo('2026-02-28 00:00:00');
    $site = autoBillingSite('Alpha Net');
    autoBillingClient($site, autoBillingCategory($site, 'Cycle 30', 30));

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::count())->toBe(1);
});

test('a site without an administrator is skipped and the others still run', function () {
    $orphan = autoBillingSite('Orphan Net', withAdministrator: false);
    autoBillingClient($orphan, autoBillingCategory($orphan, 'Orphan 15'));
    $site = autoBillingSite('Alpha Net');
    $client = autoBillingClient($site, autoBillingCategory($site, 'Alpha 15'));

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    expect(Billing::sole()->client_id)->toBe($client->id);
});

/**
 * The request the Create Billing screen sends for a Monthly Subscription.
 */
function createMonthlySubscriptionFromUi($test, Site $site, BillingCategory $category)
{
    Laravel\Sanctum\Sanctum::actingAs(User::where('site_id', $site->id)->firstOrFail());

    return $test->postJson('/api/billings', ['billing' => [
        'billingType' => '1',
        'billingCategory' => $category->id,
        'billingCutoff' => '2026-11-20',
        'disconnectionDate' => '2026-11-23',
        'billingRemarks' => '',
        'isActive' => 1,
        'billingItems' => [['billingItemQuantity' => '1', 'billingItemRemark' => '']],
    ]]);
}

function monthlySmsSettings(Site $site, bool $enabled): void
{
    SmsSettings::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'api_key' => 'alpha-key',
        'is_enabled' => $enabled,
        'test_mode' => false,
        'connection_timeout' => 5,
        'retry_attempts' => 0,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

test('a Monthly Subscription billing created in the UI texts each client when SMS is enabled', function () {
    Http::fake(['*' => Http::response([['message_id' => 1, 'status' => 'Pending']])]);
    $site = autoBillingSite('Alpha Net');
    $category = autoBillingCategory($site, 'Cycle 15');
    autoBillingClient($site, $category);
    autoBillingClient($site, $category);
    monthlySmsSettings($site, enabled: true);

    createMonthlySubscriptionFromUi($this, $site, $category)->assertSuccessful();

    expect(Billing::where('billing_type', 1)->count())->toBe(2);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request['apikey'] === 'alpha-key'
        && str_contains($request['message'], 'PHP 1,000.00')
        && str_contains($request['message'], 'Due: Nov 20, 2026')
        && str_contains($request['message'], 'Disconnection: Nov 23, 2026'));
    expect(SmsLog::where('type', SmsLog::TYPE_BILLING_CREATED)->where('status', SmsLog::STATUS_SENT)->count())->toBe(2);
});

test('a Monthly Subscription billing created in the UI sends nothing when SMS is disabled', function (bool $hasSettings) {
    Http::fake();
    $site = autoBillingSite('Alpha Net');
    $category = autoBillingCategory($site, 'Cycle 15');
    autoBillingClient($site, $category);

    if ($hasSettings) {
        monthlySmsSettings($site, enabled: false);
    }

    createMonthlySubscriptionFromUi($this, $site, $category)->assertSuccessful();

    expect(Billing::count())->toBe(1)
        ->and(SmsLog::count())->toBe(0);
    Http::assertNothingSent();
})->with(['SMS switched off' => true, 'no SMS settings saved' => false]);

test('an automated billing sends the SMS and later counts as overdue', function () {
    Http::fake(['*' => Http::response([['message_id' => 1, 'status' => 'Pending']])]);
    $site = autoBillingSite('Alpha Net');
    autoBillingClient($site, autoBillingCategory($site, 'Cycle 15'));
    SmsSettings::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'api_key' => 'alpha-key',
        'is_enabled' => true,
        'test_mode' => false,
        'connection_timeout' => 5,
        'retry_attempts' => 0,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->artisan('app:run-automated-billing')->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request['apikey'] === 'alpha-key'
        && str_contains($request['message'], 'Due: Nov 20, 2026'));

    $this->travelTo('2026-11-21 09:00:00');
    $this->artisan('app:send-overdue-sms')->assertSuccessful();

    expect(SmsLog::where('type', SmsLog::TYPE_OVERDUE_NOTICE)->sole()->status)->toBe(SmsLog::STATUS_SENT);
    Http::assertSent(fn (Request $request) => str_contains($request['message'], 'Nov 23, 2026'));
});
