<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\Billing;
use App\Models\Internetplan;
use App\Models\Payment;
use Carbon\Carbon;

class ClientOverviewService
{
    // INTERNET PLAN
    public function getInternetPlan($data)
    {
        return Client::where('internet_plan_id', $data)
            ->get()
            ->map(function ($client) {
                return [
                    'client_id' => $client->id,
                    'internet_plan_id' => $client->internetPlan?->id,
                    'internet_plan_name' => $client->internetPlan?->name,
                ];
            });
    }

    // TICKETS
    public function getTotalActiveTicket(): int
    {
        return Ticket::where('is_active', 1)
        ->whereIn('status', ['new', 'ongoing', 'hold'])
        ->count();
    }

    // OUTSTANDING BALANCE
    public function getTotalBalance(): float
    {
        return Billing::where('is_active', 1)
            ->whereIn('billing_status', ['pending', 'partial'])
            ->sum('billing_balance');
    }

    // SUBSCRIPTION STATUS
    public function getPlanStatus()
    {
        return Client::where('is_active', 1)
            ->with('pesoWifiArea')
            ->select('id', 'name', 'device_status', 'area_id')
            ->orderBy('name')
            ->get()
            ->map(function ($client) {

                return [
                    'name' => $client->name,
                    'status' => $client->device_status ?? 'Offline',
                    'area_name' => $client->pesoWifiArea->name ?? 'No Area',
                ];
            });
    }

}
