<?php

namespace App\Services;

use App\Interfaces\DashboardInterface;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Billing;
use App\Models\Server;
use App\Models\Payment;
use App\Models\ExpenseItem;
use Carbon\Carbon;

class DashboardService implements DashboardInterface
{
    // CLIENTS
    public function getTotalClient(): int
    {
        return Client::where('is_active', 1)->count();
    }

    public function getClientGrowth(): float
    {
        $now = Carbon::now();

        $thisMonth = Client::where('is_active', 1)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonth = Client::where('is_active', 1)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    // TICKETS
    public function getTotalActiveTicket(): int
    {
        return Ticket::where('is_active', 1)
        ->whereIn('status', ['new', 'ongoing', 'hold'])
        ->count();
    }

    public function getTicketGrowth(): float
    {
        $now = Carbon::now();

        $thisMonth = Ticket::where('is_active', 1)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonth = Ticket::where('is_active', 1)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    // SUBSCRIBER FOR COLLECTION (BILLING)
    public function getTotalPendingBilling(): int
    {
        return Billing::where('is_active', 1)
        ->whereNot('billing_status', 'paid')
        ->count();
    }

    public function getBillingGrowth(): float
    {
        $now = Carbon::now();

        $thisMonth = Billing::where('is_active', 1)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonth = Billing::where('is_active', 1)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    // AMOUNT FOR COLLECTION
    public function getTotalBillingAmount(): float
    {
        return Billing::where('is_active', 1)
            ->whereNot('billing_status', 'paid')
            ->sum('billing_total');
    }

    public function getMonthlyBillingAmountGrowth(): float
    {
        $now = Carbon::now();

        // This month total billing amount
        $thisMonthTotal = Billing::where('is_active', 1)
            ->where('billing_status', 'pending')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('billing_total');

        // Last month total billing amount
        $lastMonth = $now->copy()->subMonth();

        $lastMonthTotal = Billing::where('is_active', 1)
            ->where('billing_status', 'pending')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('billing_total');

        // Avoid division by zero
        if ($lastMonthTotal == 0.0) {
            return $thisMonthTotal > 0 ? 100.0 : 0.0;
        }

        return round(
            (($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100,
            2
        );
    }

    // MONTHLY WIFI COLLECTION
    public function getMonthlytWifiCollection(): float
    {
        return Billing::where('is_active', 1)
            ->where('billing_status', 'paid')
            ->where('billing_description', 'Monthly Subscription Fee')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('billing_total');
    }

    public function getMonthlyWifiCollectionGrowth(): float
    {
        $now = Carbon::now();

        // This month total billing amount
        $thisMonthTotal = Billing::where('is_active', 1)
            ->where('billing_status', 'paid')
            ->where('billing_description', 'Monthly Subscription Fee')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('billing_total');

        // Last month total billing amount
        $lastMonth = $now->copy()->subMonth();

        $lastMonthTotal = Billing::where('is_active', 1)
            ->where('billing_status', 'paid')
            ->where('billing_description', 'Monthly Subscription Fee')
            ->whereYear('created_at', $lastMonth->month)
            ->whereMonth('created_at', $lastMonth->year)
            ->sum('billing_total');

        // Avoid division by zero
        if ($lastMonthTotal == 0.0) {
            return $thisMonthTotal > 0 ? 100.0 : 0.0;
        }

        return round(
            (($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100,
            2
        );
    }

    //SERVERS
    public function getTotalActiveServers(): int
    {
        return Server::where('is_active', 1)->count();
    }

    // MONTHLY NET PROFIT (PAYMENTS)
    public function getMonthlyProfit(): float
    {
        return Payment::where('is_active', 1)
            ->sum('amount_paid');
    }

    public function getMonthlyProfitGrowth(): float
    {
        $now = Carbon::now();

        // This month total billing amount
        $thisMonthTotal = Payment::where('is_active', 1)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount_paid');

        // Last month total billing amount
        $lastMonth = $now->copy()->subMonth();

        $lastMonthTotal = Payment::where('is_active', 1)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('amount_paid');

        // Avoid division by zero
        if ($lastMonthTotal == 0.0) {
            return $thisMonthTotal > 0 ? 100.0 : 0.0;
        }

        return round(
            (($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100,
            2
        );
    }

    // GET THE OVERDUE BILLINGS
    public function getOverdueBillings()
    {
        $today = Carbon::today();

        return Billing::with('client')
            ->where('is_active', 1)
            ->whereIn('billing_status', ['overdue', 'due'])
            ->whereNotNull('disconnection_date')
            ->orderBy('disconnection_date', 'asc')
            ->take(5)
            ->get()
            ->map(function ($billing) use ($today) {

                $daysOverdue = 0;

                if ($billing->disconnection_date) {
                    $disconnectionDate = Carbon::parse($billing->disconnection_date);

                    if ($today->gt($disconnectionDate)) {
                        $daysOverdue = $disconnectionDate->diffInDays($today);
                    }
                }

                return [
                    'invoice_number' => $billing->invoice_number,
                    'client_name' => $billing->client->first_name . ' ' . $billing->client->last_name,
                    'contact_no' => $billing->client->mobile_no,
                    'balance' => $billing->billing_balance,
                    'days_overdue' => $daysOverdue,
                    'status' => $daysOverdue > 0
                                    ? 'For Disconnection'
                                    : 'Due',
                ];
            });
    }

}
