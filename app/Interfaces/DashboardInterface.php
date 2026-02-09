<?php

namespace App\Interfaces;

interface DashboardInterface
{
    public function getTotalClient(): int;
    public function getClientGrowth(): float;
    public function getTotalActiveTicket(): int;
    public function getTicketGrowth(): float;
    public function getTotalPendingBilling(): int;
    public function getBillingGrowth(): float;
    public function getTotalBillingAmount(): float;
    public function getMonthlyBillingAmountGrowth(): float;
    public function getTotalActiveServers(): int;
    public function getMonthlyExpenseAmount(): float;
    public function getExpenseGrowth(): float;
}
