<?php

use App\Libraries\Billing\MonthlySubscription;
use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\Internetplan;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $site = Site::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_url' => 'billing.test',
        'company_name' => 'Billing Test',
        'company_address' => 'Cebu',
        'company_email' => 'admin@billing.test',
        'company_phone' => '0900',
        'company_telephone' => '0900',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $role = Role::forceCreate([
        'site_id' => $site->id,
        'name' => 'Admin',
        'description' => 'Admin',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    Sanctum::actingAs(User::create([
        'site_id' => $site->id,
        'fullname' => 'Staff Member',
        'username' => 'staff',
        'email' => 'staff@billing.test',
        'password' => 'secret-password',
        'role_id' => $role->id,
    ]));

    $this->category = BillingCategory::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'name' => 'Cycle 25',
        'date_cycle' => 25,
        'days_to_due_date' => 5,
        'days_to_disconnection_date' => 5,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $plan = Internetplan::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'name' => 'SMALL',
        'monthly_subscription' => '1000',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    $this->client = Client::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $site->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'mobile_no' => '09171234567',
        'house_no' => '123 Rizal',
        'account_no' => '01-611',
        'installation_date' => '2024-08-29',
        'billing_category_id' => $this->category->id,
        'server_id' => 1,
        'internet_plan_id' => $plan->id,
        'balance_from_prev_billing_status' => 'Billed',
        'prorate_fee_status' => 'Billed',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
});

function generateSubscriptions(int $categoryId, string $cutoff): void
{
    app(BillingService::class)->generateBilling(new MonthlySubscription, [
        'billingType' => '1',
        'billingCategory' => $categoryId,
        'billingCutoff' => $cutoff,
        'disconnectionDate' => $cutoff,
        'billingItems' => [['billingItemQuantity' => '1']],
    ]);
}

function existingSubscription(Client $client, string $date, string $cutoff, string $status, float $balance = 1000): Billing
{
    return Billing::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $client->site_id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-' . Str::random(6),
        'billing_type' => 1,
        'billing_date' => $date,
        'billing_total' => 1000,
        'billing_balance' => $balance,
        'billing_status' => $status,
        'billing_cutoff' => $cutoff,
        'balance_from_prev_billing' => 0,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

test('an unpaid billing from last month does not skip the client this month', function () {
    existingSubscription($this->client, '2026-09-25 08:00:00', '2026-09-30', 'Pending');

    generateSubscriptions($this->category->id, '2026-10-30');

    $billing = Billing::where('billing_cutoff', '2026-10-30')->sole();

    expect((float) $billing->balance_from_prev_billing)->toBe(1000.0)
        ->and((float) $billing->billing_balance)->toBe(1000.0)
        ->and((float) $this->client->fresh()->current_balance)->toBe(2000.0);
});

test('a billing generated late still counts for its own cycle only', function () {
    existingSubscription($this->client, '2026-11-09 08:00:00', '2026-10-30', 'Pending');

    generateSubscriptions($this->category->id, '2026-11-30');

    expect(Billing::where('billing_cutoff', '2026-11-30')->count())->toBe(1);
});

test('consecutive cycles with cut-offs in the same month are both billed', function () {
    existingSubscription($this->client, '2026-02-26 08:00:00', '2026-03-03', 'Pending');

    generateSubscriptions($this->category->id, '2026-03-31');

    expect(Billing::where('billing_cutoff', '2026-03-31')->count())->toBe(1);
});

test('a client already billed for the cycle is not billed again', function (string $status) {
    existingSubscription($this->client, '2026-10-25 08:00:00', '2026-10-30', $status);

    expect(fn () => generateSubscriptions($this->category->id, '2026-10-30'))
        ->toThrow(Exception::class, 'No Client found.');

    expect(Billing::count())->toBe(1);
})->with(['Pending', 'Partial', 'Paid']);

test('a deactivated billing does not count as billed for the cycle', function () {
    existingSubscription($this->client, '2026-10-25 08:00:00', '2026-10-30', 'Pending')
        ->update(['is_active' => 0]);

    generateSubscriptions($this->category->id, '2026-10-30');

    expect(Billing::where('is_active', 1)->count())->toBe(1);
});

test('batch print returns only the latest matching billing of each client', function () {
    $other = $this->client->replicate(['uuid']);
    $other->uuid = (string) Str::uuid();
    $other->account_no = '01-612';
    $other->save();

    existingSubscription($this->client, '2026-09-25 08:00:00', '2026-09-30', 'Pending');
    $latest = existingSubscription($this->client, '2026-10-25 08:00:00', '2026-10-30', 'Pending');
    $otherLatest = existingSubscription($other, '2026-09-25 08:00:00', '2026-09-30', 'Pending');
    existingSubscription($this->client, '2026-11-25 08:00:00', '2026-11-30', 'Paid');

    $response = $this->getJson('/api/billings/find?latest_per_client=1&status[]=Pending')->assertOk();

    expect(collect($response->json('data'))->pluck('attributes.uuid')->sort()->values()->all())
        ->toBe(collect([$latest->uuid, $otherLatest->uuid])->sort()->values()->all());
});

test('the billing list still returns every billing without the print filter', function () {
    existingSubscription($this->client, '2026-09-25 08:00:00', '2026-09-30', 'Pending');
    existingSubscription($this->client, '2026-10-25 08:00:00', '2026-10-30', 'Pending');

    $this->getJson('/api/billings/find?status[]=Pending')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
