<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Owns the login account that every client gets for the client portal.
 */
class ClientUserService
{
    public const DEFAULT_PASSWORD = 'password';
    public const PORTAL_MODULE_URL = 'clientsdashboard';

    public function createForClient(array $clientAttributes, int $siteId): User
    {
        $firstName = $clientAttributes['first_name'];
        $lastName = $clientAttributes['last_name'];

        return User::create([
            'site_id' => $siteId,
            'fullname' => trim("{$firstName} {$lastName}"),
            'username' => $this->generateUsername($firstName, $lastName, $siteId),
            'email' => $this->availableEmail($clientAttributes['email'] ?? null, $siteId),
            'password' => self::DEFAULT_PASSWORD,
            'must_change_password' => true,
            'role_id' => $this->clientRole($siteId)->id,
        ]);
    }

    public function createForExistingClient(Client $client): User
    {
        $user = $this->createForClient($client->only(['first_name', 'last_name', 'email']), $client->site_id);

        $client->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Keeps the login in step with the client record. The username is left alone
     * so that correcting a client's name doesn't change how they log in.
     */
    public function syncFromClient(Client $client): void
    {
        $user = $client->user;

        if (!$user?->isClient()) {
            return;
        }

        $user->update([
            'fullname' => trim("{$client->first_name} {$client->last_name}"),
            'email' => $this->availableEmail($client->email, $user->site_id, $user->id),
            'is_active' => $client->is_active,
        ]);

        if (!$client->is_active) {
            $user->tokens()->delete();
        }
    }

    public function deleteUser(?User $user): void
    {
        if (!$user?->isClient()) {
            return;
        }

        $user->tokens()->delete();
        $user->delete();
    }

    /**
     * First letter of the first name + last name, i.e. Juan Dela Cruz is jdelacruz.
     * The next person with the same name in the site becomes jdelacruz2.
     */
    private function generateUsername(string $firstName, string $lastName, int $siteId): string
    {
        $firstLetter = Str::substr(Str::ascii(trim($firstName)), 0, 1);
        $base = preg_replace('/[^a-z0-9]/', '', Str::lower($firstLetter . Str::ascii($lastName)));

        $username = $base;
        $suffix = 2;

        while (User::withoutGlobalScopes()->where('site_id', $siteId)->where('username', $username)->exists()) {
            $username = $base . $suffix++;
        }

        return $username;
    }

    /**
     * A client's email is optional and may already be used by a staff account,
     * neither of which should stop the client from being created.
     */
    private function availableEmail(?string $email, int $siteId, ?int $ignoreUserId = null): ?string
    {
        if (empty($email)) {
            return null;
        }

        $isTaken = User::withoutGlobalScopes()
            ->where('site_id', $siteId)
            ->where('email', $email)
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();

        return $isTaken ? null : $email;
    }

    private function clientRole(int $siteId): Role
    {
        // nobody is logged in when this runs from the console command
        $createdBy = auth()->id() ?? 1;

        $role = Role::withoutGlobalScopes()->where('site_id', $siteId)->where('name', 'Client')->first()
            ?? Role::forceCreate([
                'site_id' => $siteId,
                'name' => 'Client',
                'description' => 'Client portal access',
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);

        $module = $this->portalModule();

        if (!$role->modules()->where('modules.id', $module->id)->exists()) {
            Permission::forceCreate([
                'site_id' => $siteId,
                'role_id' => $role->id,
                'module_id' => $module->id,
                'is_read' => 1,
                'is_write' => 0,
                'is_delete' => 0,
                'is_active' => 1,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);
        }

        return $role;
    }

    private function portalModule(): Module
    {
        return Module::where('url', self::PORTAL_MODULE_URL)->first()
            ?? Module::forceCreate([
                'name' => 'Client Overview',
                'icon' => 'fa fa-tachometer',
                'parent_id' => 0,
                'description' => 'Manage clients dashboard',
                'url' => self::PORTAL_MODULE_URL,
                'is_active' => 1,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);
    }
}
