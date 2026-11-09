<?php

namespace App\Services\Mikrotik;

use App\Models\Billing;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single definition of "this client should be cut off". Bills are the
 * source of truth: clients.latest_disconnection_date is overwritten by every
 * bill type and never cleared by payments, and clients.current_balance can drift.
 */
class SuspensionPolicy
{
    private const UNPAID_STATUSES = ['Pending', 'Partial'];

    public function isOverdue(Client $client, string $today): bool
    {
        return $this->overdueBills($client->site_id, $today)
            ->where('client_id', $client->id)
            ->whereIn('client_id', $this->notExempt($client->site_id, $today))
            ->exists();
    }

    /** @return array<int, int> */
    public function overdueClientIds(int $siteId, int $routerId, string $today): array
    {
        return $this->overdueBills($siteId, $today)
            ->whereIn('client_id', $this->notExempt($siteId, $today)->where('mikrotik_id', $routerId))
            ->distinct()
            ->pluck('client_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function overdueBills(int $siteId, string $today): Builder
    {
        return Billing::withoutGlobalScopes()
            ->where('site_id', $siteId)
            ->where('is_active', 1)
            ->whereIn('billing_status', self::UNPAID_STATUSES)
            ->where('billing_balance', '>', 0)
            ->whereNotNull('disconnection_date')
            ->whereDate('disconnection_date', '<=', $today);
    }

    private function notExempt(int $siteId, string $today): Builder
    {
        return Client::withoutGlobalScopes()
            ->select('id')
            ->where('site_id', $siteId)
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('suspension_exempt_until')
                    ->orWhereDate('suspension_exempt_until', '<', $today);
            });
    }
}
