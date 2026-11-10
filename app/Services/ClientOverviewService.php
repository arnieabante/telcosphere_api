<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Ticket;

class ClientOverviewService
{
    public function getSummary(Client $client): array
    {
        return [
            'clientName' => trim("{$client->first_name} {$client->last_name}"),
            'accountNo' => $client->account_no,
            'subscriptionStatus' => $client->is_active ? 'Active' : 'Inactive',
            'internetPlan' => $client->internetPlan?->name,
            // also carries the opening balance entered when the client was added, which has no billing
            'outstandingBalance' => (float) $client->current_balance,
            'activeTickets' => $this->countActiveTickets($client),
        ];
    }

    private function countActiveTickets(Client $client): int
    {
        return Ticket::where('client_id', $client->id)
            ->where('is_active', 1)
            ->where('status', '!=', 'done')
            ->count();
    }
}
