<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\ClientUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateClientPortalUsers extends Command
{
    protected $signature = 'clients:create-portal-users
                            {--site= : Only process clients of this site id}
                            {--dry-run : Show the logins that would be created without saving anything}';

    protected $description = 'Creates portal logins for active clients that do not have one yet. Safe to run again.';

    public function handle(ClientUserService $clientUsers): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $clients = Client::withoutGlobalScopes()
            ->whereNull('user_id')
            ->where('is_active', 1)
            ->when($this->option('site'), fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->orderBy('site_id')
            ->orderBy('id')
            ->get();

        $rows = [];
        $failures = 0;

        // a dry run does the real work and rolls it back, so the usernames shown are exactly what a real run gives
        if ($dryRun) {
            DB::beginTransaction();
        }

        foreach ($clients as $client) {
            try {
                $user = DB::transaction(fn () => $clientUsers->createForExistingClient($client));

                $rows[] = [$client->site_id, $client->account_no, "{$client->first_name} {$client->last_name}", $user->username];
            } catch (\Throwable $ex) {
                $failures++;
                $this->error("Client {$client->id} ({$client->first_name} {$client->last_name}): {$ex->getMessage()}");
            }
        }

        if ($dryRun) {
            DB::rollBack();
        }

        $this->table(['Site', 'Account No', 'Client', 'Username'], $rows);

        $this->info(($dryRun ? 'Dry run, nothing was saved. Would create ' : 'Created ') . count($rows) . ' login(s).');

        if ($failures > 0) {
            $this->error("{$failures} client(s) failed, see above.");
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
