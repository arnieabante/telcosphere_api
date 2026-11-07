<?php

use App\Models\Billing;
use App\Models\Client;
use App\Models\Role;
use App\Models\Site;
use App\Models\SiteBankAccount;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function portalSite(string $url = 'portal.test'): Site
{
    return Site::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_url' => $url,
        'company_name' => $url,
        'company_address' => 'Cebu',
        'company_email' => "admin@{$url}",
        'company_phone' => '0900',
        'company_telephone' => '0900',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

function portalStaff(int $siteId, string $username = 'staff'): User
{
    $role = Role::forceCreate([
        'site_id' => $siteId,
        'name' => 'Admin',
        'description' => 'Admin',
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    return User::create([
        'site_id' => $siteId,
        'fullname' => 'Staff Member',
        'username' => $username,
        'email' => "{$username}@site{$siteId}.test",
        'password' => 'secret-password',
        'role_id' => $role->id,
    ]);
}

function clientPayload(array $overrides = []): array
{
    return array_merge([
        'firstName' => 'Juan',
        'lastName' => 'Dela Cruz',
        'mobileNo' => '09171234567',
        'houseNo' => '123 Rizal Street',
        'accountNo' => 'ACC-' . Str::random(6),
        'installationDate' => '2026-01-01',
        'prorateFee' => 0,
        'billingCategoryId' => 1,
        'serverId' => 1,
        'internetPlanId' => 1,
        'isActive' => '1',
    ], $overrides);
}

/** Creates a client through the API and returns the client with its login. */
function addClient(User $staff, array $overrides = []): Client
{
    Sanctum::actingAs($staff);

    $uuid = test()->postJson('/api/clients', clientPayload($overrides))
        ->assertCreated()
        ->json('data.attributes.uuid');

    return Client::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();
}

function loginAs(string $username, Site $site, string $password = 'password')
{
    // a real login request starts out unauthenticated on the default guard
    resetAuth();

    return test()->postJson('/api/login', [
        'username' => $username,
        'password' => $password,
        'siteId' => $site->id,
    ]);
}

test('adding a client creates a login with the default username and password', function () {
    $site = portalSite();
    $client = addClient(portalStaff($site->id));

    expect($client->user->username)->toBe('jdelacruz')
        ->and($client->user->isClient())->toBeTrue()
        ->and($client->user->site_id)->toBe($site->id);

    resetAuth();

    loginAs('jdelacruz', $site)
        ->assertOk()
        ->assertJsonPath('data.user.clientUuid', $client->uuid)
        ->assertJsonPath('data.user.firstModule', 'clientsdashboard');
});

test('clients with the same name, no email and accented names can all be added', function () {
    $staff = portalStaff(portalSite()->id);

    expect(addClient($staff)->user->username)->toBe('jdelacruz')
        ->and(addClient($staff, ['firstName' => 'Jose'])->user->username)->toBe('jdelacruz2')
        ->and(addClient($staff, ['firstName' => 'Ñino', 'lastName' => "Peña-O'Neil"])->user->username)->toBe('npenaoneil');
});

test('a client whose email belongs to a staff account can still be added', function () {
    $staff = portalStaff(portalSite()->id);
    $client = addClient($staff, ['email' => $staff->email]);

    expect($client->email)->toBe($staff->email)
        ->and($client->user->email)->toBeNull();
});

test('no login is left behind when the client fails to save', function () {
    $staff = portalStaff(portalSite()->id);
    Sanctum::actingAs($staff);

    Client::creating(fn () => throw new RuntimeException('Failed to save.'));

    $this->postJson('/api/clients', clientPayload())->assertServerError();

    expect(User::withoutGlobalScopes()->where('username', 'jdelacruz')->exists())->toBeFalse();
});

test('the same username in two sites logs in to the correct account', function () {
    $siteA = portalSite('a.test');
    $siteB = portalSite('b.test');

    addClient(portalStaff($siteA->id, 'staffa'), ['email' => 'a@client.test']);
    $clientB = addClient(portalStaff($siteB->id, 'staffb'), ['email' => 'b@client.test']);
    $clientB->user->update(['password' => 'changed-password']);

    resetAuth();

    loginAs('jdelacruz', $siteB, 'changed-password')
        ->assertOk()
        ->assertJsonPath('data.user.clientUuid', $clientB->uuid);
});

test('a client login cannot reach the staff api', function (string $method, string $uri) {
    $site = portalSite();
    $client = addClient(portalStaff($site->id));

    actAsClient($client);

    $this->json($method, $uri)->assertForbidden();
})->with([
    ['GET', '/api/clients'],
    ['GET', '/api/clients/find'],
    ['GET', '/api/users'],
    ['POST', '/api/users'],
    ['GET', '/api/tickets'],
    ['POST', '/api/tickets'],
    ['GET', '/api/billings'],
    ['GET', '/api/payments'],
    ['GET', '/api/roles'],
    ['GET', '/api/sites'],
]);

test('a client login can read its own role but no other, and no other client', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $other = addClient($staff, ['firstName' => 'Maria', 'lastName' => 'Santos']);

    $user = $client->user->fresh();
    Sanctum::actingAs($user);

    $this->getJson("/api/roles/{$user->role->uuid}")->assertOk();
    $this->getJson("/api/roles/{$staff->role->uuid}")->assertForbidden();
    $this->getJson("/api/clients/{$other->uuid}")->assertForbidden();
    $this->getJson("/api/clients/{$client->uuid}")->assertForbidden();
    $this->getJson("/api/clients/{$other->uuid}/soa")->assertForbidden();
});

test('staff are unaffected by the restriction and have no portal', function () {
    $staff = portalStaff(portalSite()->id);
    Sanctum::actingAs($staff);

    $this->getJson('/api/clients')->assertOk();
    $this->getJson('/api/clientsdashboard')->assertForbidden();
});

test('the overview only counts the logged in client', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $other = addClient($staff, ['firstName' => 'Maria', 'lastName' => 'Santos']);
    $client->update(['current_balance' => 2999]);

    $category = TicketCategory::forceCreate(portalCategory($site->id));

    foreach ([[$client, 'new'], [$client, 'ongoing'], [$client, 'done'], [$other, 'new']] as [$owner, $status]) {
        portalTicket($owner, $category, $status);
    }

    actAsClient($client);

    $this->getJson('/api/clientsdashboard')
        ->assertOk()
        ->assertJsonPath('data.clientName', 'Juan Dela Cruz')
        ->assertJsonPath('data.subscriptionStatus', 'Active')
        ->assertJsonPath('data.activeTickets', 2)
        ->assertJsonPath('data.outstandingBalance', fn ($balance) => (float) $balance === 2999.0);
});

test('billings and tickets only list the logged in client', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $other = addClient($staff, ['firstName' => 'Maria', 'lastName' => 'Santos']);
    $category = TicketCategory::forceCreate(portalCategory($site->id));

    portalBilling($client, 'INV-MINE');
    portalBilling($other, 'INV-OTHER');
    portalTicket($client, $category, 'new', 'Mine');
    portalTicket($other, $category, 'new', 'Other');

    actAsClient($client);

    $this->getJson('/api/clientsdashboard/billings')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.invoiceNumber', 'INV-MINE')
        ->assertJsonPath('data.0.attributes.amountDue', '800.00');

    $tickets = $this->getJson('/api/clientsdashboard/tickets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.name', 'Mine');

    expect($tickets->json('data.0'))->not->toHaveKey('relationships');
});

test('a client files a ticket that staff see as new', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $other = addClient($staff, ['firstName' => 'Maria', 'lastName' => 'Santos']);
    $category = TicketCategory::forceCreate(portalCategory($site->id));

    actAsClient($client);

    // ownership and status in the request are ignored
    $this->postJson('/api/clientsdashboard/tickets', [
        'name' => 'No Internet',
        'description' => str_repeat('a', 200),
        'categoryId' => $category->id,
        'clientId' => $other->id,
        'status' => 'done',
    ])->assertCreated()->assertJsonPath('data.attributes.status', 'new');

    $ticket = Ticket::withoutGlobalScopes()->where('name', 'No Internet')->firstOrFail();

    expect($ticket->client_id)->toBe($client->id)
        ->and($ticket->requestor_name)->toBe('Juan Dela Cruz')
        ->and($ticket->requestor_location)->toBe('123 Rizal Street')
        ->and($ticket->ticket_type)->toBe('Client')
        ->and($ticket->site_id)->toBe($site->id);

    resetAuth();
    Sanctum::actingAs($staff);

    $this->getJson('/api/tickets?status=new')->assertOk()->assertJsonCount(1, 'data');
});

test('a ticket needs a category from the client site', function () {
    $siteA = portalSite('a.test');
    $siteB = portalSite('b.test');
    $client = addClient(portalStaff($siteA->id));
    $foreign = TicketCategory::forceCreate(portalCategory($siteB->id));

    actAsClient($client);

    $this->postJson('/api/clientsdashboard/tickets', ['name' => 'No Internet', 'categoryId' => $foreign->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('categoryId');
});

test('deactivating a client blocks the login and reactivating restores it', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $client->user->createToken('existing');

    Sanctum::actingAs($staff);
    $this->patchJson("/api/clients/{$client->uuid}", ['isActive' => 0])->assertOk();

    $user = $client->user->fresh();
    expect((bool) $user->is_active)->toBeFalse()
        ->and($user->tokens()->count())->toBe(0);

    resetAuth();
    loginAs('jdelacruz', $site)->assertForbidden();

    resetAuth();
    Sanctum::actingAs($staff);
    $this->patchJson("/api/clients/{$client->uuid}", ['isActive' => 1])->assertOk();

    resetAuth();
    loginAs('jdelacruz', $site)->assertOk();
});

test('editing a client updates the login name but keeps the username', function () {
    $staff = portalStaff(portalSite()->id);
    $client = addClient($staff);

    Sanctum::actingAs($staff);
    $this->patchJson("/api/clients/{$client->uuid}", ['lastName' => 'Reyes', 'email' => 'juan@client.test'])->assertOk();

    $user = $client->user->fresh();

    expect($user->fullname)->toBe('Juan Reyes')
        ->and($user->email)->toBe('juan@client.test')
        ->and($user->username)->toBe('jdelacruz');
});

test('a client cannot be linked to an arbitrary user through the request', function () {
    $staff = portalStaff(portalSite()->id);
    $client = addClient($staff, ['userId' => $staff->id]);

    expect($client->user_id)->not->toBe($staff->id);

    Sanctum::actingAs($staff);
    $this->patchJson("/api/clients/{$client->uuid}", ['userId' => $staff->id])->assertOk();

    expect($client->fresh()->user_id)->not->toBe($staff->id);
});

test('deleting a client removes its login but never a staff account', function () {
    $staff = portalStaff(portalSite()->id);
    $client = addClient($staff);
    $userId = $client->user_id;

    Sanctum::actingAs($staff);
    $this->deleteJson("/api/clients/{$client->uuid}")->assertOk();

    expect(User::withoutGlobalScopes()->find($userId))->toBeNull()
        ->and(User::withoutGlobalScopes()->find($staff->id))->not->toBeNull();
});

test('client logins stay out of the staff dropdowns', function () {
    $staff = portalStaff(portalSite()->id);
    addClient($staff);

    Sanctum::actingAs($staff);

    $usernames = collect($this->getJson('/api/users?include=all')->assertOk()->json('data'))
        ->pluck('attributes.username');

    expect($usernames->all())->toBe(['staff']);
});

test('a new client must change the default password before using the portal', function () {
    $site = portalSite();
    $client = addClient(portalStaff($site->id));

    $login = loginAs('jdelacruz', $site)
        ->assertOk()
        ->assertJsonPath('data.user.mustChangePassword', true);

    resetAuth();
    $headers = ['Authorization' => 'Bearer ' . $login->json('data.token')];

    foreach (['/api/clientsdashboard', '/api/clientsdashboard/billings', '/api/clientsdashboard/tickets'] as $uri) {
        $this->getJson($uri, $headers)->assertForbidden();
        resetAuth();
    }

    $this->postJson('/api/clientsdashboard/tickets', ['name' => 'x'], $headers)->assertForbidden();
    resetAuth();

    $this->postJson('/api/change-password', [
        'currentPassword' => 'password',
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ], $headers)->assertOk();
    resetAuth();

    $this->getJson('/api/clientsdashboard', $headers)->assertOk();

    expect($client->user->fresh()->must_change_password)->toBeFalse();

    loginAs('jdelacruz', $site)->assertUnauthorized();
    loginAs('jdelacruz', $site, 'my-new-password')
        ->assertOk()
        ->assertJsonPath('data.user.mustChangePassword', false);
});

test('the new password is validated', function (array $payload, string $errorField) {
    $client = addClient(portalStaff(portalSite()->id));

    Sanctum::actingAs($client->user->fresh());

    $this->postJson('/api/change-password', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errorField);

    expect($client->user->fresh()->must_change_password)->toBeTrue();
})->with([
    'wrong current password' => [
        ['currentPassword' => 'not-my-password', 'password' => 'my-new-password', 'password_confirmation' => 'my-new-password'],
        'currentPassword',
    ],
    'still the default' => [
        ['currentPassword' => 'password', 'password' => 'password', 'password_confirmation' => 'password'],
        'password',
    ],
    'too short' => [
        ['currentPassword' => 'password', 'password' => 'short', 'password_confirmation' => 'short'],
        'password',
    ],
    'not confirmed' => [
        ['currentPassword' => 'password', 'password' => 'my-new-password', 'password_confirmation' => 'different-one'],
        'password',
    ],
]);

test('changing the password signs out other sessions but keeps this one', function () {
    $site = portalSite();
    addClient(portalStaff($site->id));

    $other = loginAs('jdelacruz', $site)->json('data.token');
    $current = loginAs('jdelacruz', $site)->json('data.token');
    resetAuth();

    $this->postJson('/api/change-password', [
        'currentPassword' => 'password',
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ], ['Authorization' => "Bearer {$current}"])->assertOk();
    resetAuth();

    $this->getJson('/api/clientsdashboard', ['Authorization' => "Bearer {$current}"])->assertOk();
    resetAuth();

    $this->getJson('/api/clientsdashboard', ['Authorization' => "Bearer {$other}"])->assertUnauthorized();
});

test('staff resetting a client password forces another change, but not for staff accounts', function () {
    $staff = portalStaff(portalSite()->id);
    $client = addClient($staff);
    $client->user->update(['must_change_password' => false]);

    Sanctum::actingAs($staff);

    $this->patchJson("/api/users/{$client->user->uuid}", ['password' => 'temporary-pass'])->assertOk();
    $this->patchJson("/api/users/{$staff->uuid}", ['password' => 'new-staff-pass'])->assertOk();

    expect($client->user->fresh()->must_change_password)->toBeTrue()
        ->and($staff->fresh()->must_change_password)->toBeFalse();
});

test('an inactive client cannot log in even when the login itself is still active', function () {
    $site = portalSite();
    $client = addClient(portalStaff($site->id));

    // i.e. a change that did not go through the clients api
    Client::withoutGlobalScopes()->where('id', $client->id)->update(['is_active' => 0]);

    loginAs('jdelacruz', $site)->assertForbidden();
});

test('the command creates logins for active clients only and can be run again', function () {
    $site = portalSite();
    $base = existingClientAttributes($site->id);

    Client::forceCreate($base + ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'account_no' => 'A1']);
    Client::forceCreate($base + ['first_name' => 'Jose', 'last_name' => 'Dela Cruz', 'account_no' => 'A2', 'email' => 'jose@client.test']);
    $inactive = Client::forceCreate($base + ['first_name' => 'Old', 'last_name' => 'Client', 'account_no' => 'A3', 'is_active' => 0]);

    $this->artisan('clients:create-portal-users', ['--dry-run' => true])->assertSuccessful();

    expect(User::withoutGlobalScopes()->count())->toBe(0);

    $this->artisan('clients:create-portal-users')->assertSuccessful();

    $usernames = Client::withoutGlobalScopes()->with('user')->where('is_active', 1)->orderBy('account_no')->get()->pluck('user.username');

    expect($usernames->all())->toBe(['jdelacruz', 'jdelacruz2'])
        ->and($inactive->fresh()->user_id)->toBeNull()
        ->and(User::withoutGlobalScopes()->where('must_change_password', true)->count())->toBe(2);

    $this->artisan('clients:create-portal-users')->assertSuccessful();

    expect(User::withoutGlobalScopes()->count())->toBe(2);

    loginAs('jdelacruz', $site)->assertOk()->assertJsonPath('data.user.mustChangePassword', true);
});

test('the command can be limited to one site', function () {
    $siteA = portalSite('a.test');
    $siteB = portalSite('b.test');
    $name = ['first_name' => 'Juan', 'last_name' => 'Dela Cruz'];

    $a = Client::forceCreate(existingClientAttributes($siteA->id) + $name + ['account_no' => 'A1']);
    $b = Client::forceCreate(existingClientAttributes($siteB->id) + $name + ['account_no' => 'B1']);

    $this->artisan('clients:create-portal-users', ['--site' => $siteB->id])->assertSuccessful();

    expect($a->fresh()->user_id)->toBeNull()
        ->and($b->fresh()->user_id)->not->toBeNull();
});

test('site admins save one set of bank details per site and can replace the qr', function () {
    Storage::fake('public');

    $siteA = portalSite('a.test');
    $siteB = portalSite('b.test');
    $staffA = portalStaff($siteA->id, 'staffa');
    $staffB = portalStaff($siteB->id, 'staffb');
    $json = ['Accept' => 'application/json'];

    Sanctum::actingAs($staffA);

    $this->getJson('/api/sitebankaccount')->assertOk()->assertJsonPath('data', null);

    $first = $this->post('/api/sitebankaccount', [
        'bankName' => 'BPI',
        'accountName' => 'Telco Inc',
        'accountNumber' => '1234',
        'accountQR' => UploadedFile::fake()->image('qr.png'),
    ], $json)->assertCreated();

    $firstQr = SiteBankAccount::first()->account_qr;
    Storage::disk('public')->assertExists($firstQr);
    expect($first->json('data.attributes.qrUrl'))->toContain("storage/{$firstQr}");

    // saving without a new file keeps the qr, a new file replaces the old one
    $this->post('/api/sitebankaccount', ['bankName' => 'BDO', 'accountName' => 'Telco Inc', 'accountNumber' => '5678'], $json)
        ->assertOk()
        ->assertJsonPath('data.attributes.bankName', 'BDO');
    expect(SiteBankAccount::first()->account_qr)->toBe($firstQr);

    $this->post('/api/sitebankaccount', [
        'bankName' => 'BDO',
        'accountName' => 'Telco Inc',
        'accountNumber' => '5678',
        'accountQR' => UploadedFile::fake()->image('new.png'),
    ], $json)->assertOk();
    Storage::disk('public')->assertMissing($firstQr);

    expect(SiteBankAccount::withoutGlobalScopes()->count())->toBe(1);

    resetAuth();
    Sanctum::actingAs($staffB);

    // another site neither sees nor overwrites them
    $this->getJson('/api/sitebankaccount')->assertOk()->assertJsonPath('data', null);
});

test('bank details need every field and an image qr', function () {
    Storage::fake('public');
    Sanctum::actingAs(portalStaff(portalSite()->id));

    $this->post('/api/sitebankaccount', [
        'accountQR' => UploadedFile::fake()->create('qr.svg', 10, 'image/svg+xml'),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bankName', 'accountName', 'accountNumber', 'accountQR']);
});

test('a client cannot read or change the site bank details through the staff api', function () {
    $client = addClient(portalStaff(portalSite()->id));

    actAsClient($client);

    $this->getJson('/api/sitebankaccount')->assertForbidden();
    $this->postJson('/api/sitebankaccount', ['bankName' => 'x', 'accountName' => 'x', 'accountNumber' => 'x'])->assertForbidden();
});

test('the payment page shows the amount due and the site bank details', function () {
    $site = portalSite();
    $client = addClient(portalStaff($site->id));
    $billing = portalBilling($client, 'INV-1');

    SiteBankAccount::forceCreate([
        'site_id' => $site->id,
        'bank_name' => 'BPI',
        'account_name' => 'Telco Inc',
        'account_number' => '1234',
        'account_qr' => 'account_qr/qr.png',
        'is_active' => 1,
    ]);

    actAsClient($client);

    $this->getJson("/api/clientsdashboard/billings/{$billing->uuid}/payment")
        ->assertOk()
        ->assertJsonPath('data.billing.attributes.invoiceNumber', 'INV-1')
        ->assertJsonPath('data.billing.attributes.billingBalance', fn ($balance) => (float) $balance === 800.0)
        ->assertJsonPath('data.bankAccount.attributes.bankName', 'BPI')
        ->assertJsonPath('data.bankAccount.attributes.accountNumber', '1234')
        ->assertJsonPath('data.bankAccount.attributes.qrUrl', fn ($url) => str_ends_with($url, 'storage/account_qr/qr.png'));
});

test('the payment page works before bank details exist and hides invoices of other clients', function () {
    $site = portalSite();
    $staff = portalStaff($site->id);
    $client = addClient($staff);
    $other = addClient($staff, ['firstName' => 'Maria', 'lastName' => 'Santos']);
    $mine = portalBilling($client, 'INV-MINE');
    $theirs = portalBilling($other, 'INV-OTHER');

    actAsClient($client);

    $this->getJson("/api/clientsdashboard/billings/{$mine->uuid}/payment")
        ->assertOk()
        ->assertJsonPath('data.bankAccount', null);

    $this->getJson("/api/clientsdashboard/billings/{$theirs->uuid}/payment")->assertNotFound();
});

/** Acts as a client that has already been through the first login password change. */
function actAsClient(Client $client): void
{
    $client->user->update(['must_change_password' => false]);

    Sanctum::actingAs($client->user->fresh());
}

/** A client from before portal logins existed, nobody is logged in when these run. */
function existingClientAttributes(int $siteId): array
{
    return [
        'site_id' => $siteId,
        'mobile_no' => '09171234567',
        'house_no' => '123 Rizal',
        'installation_date' => '2026-01-01',
        'billing_category_id' => 1,
        'server_id' => 1,
        'internet_plan_id' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ];
}

function portalCategory(int $siteId): array
{
    return [
        'uuid' => (string) Str::uuid(),
        'site_id' => $siteId,
        'name' => 'Repairs',
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ];
}

function portalTicket(Client $client, TicketCategory $category, string $status, string $name = 'Issue'): Ticket
{
    return Ticket::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $client->site_id,
        'ticket_type' => 'Client',
        'client_id' => $client->id,
        'requestor_name' => 'Requestor',
        'name' => $name,
        'category_id' => $category->id,
        'requested_date' => today(),
        'status' => $status,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

function portalBilling(Client $client, string $invoice): Billing
{
    return Billing::forceCreate([
        'uuid' => (string) Str::uuid(),
        'site_id' => $client->site_id,
        'client_id' => $client->id,
        'invoice_number' => $invoice,
        'billing_type' => 1,
        'billing_date' => now(),
        'billing_description' => 'Monthly Subscription',
        'billing_total' => 1000,
        'advance_payment' => 200,
        'billing_offset' => 0,
        'billing_balance' => 800,
        'billing_status' => 'Pending',
        'balance_from_prev_billing' => 0,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

/** Each request in a test should start out unauthenticated, as a real one does. */
function resetAuth(): void
{
    auth()->forgetGuards();
    auth()->setDefaultDriver('web');
    session()->flush();
}
