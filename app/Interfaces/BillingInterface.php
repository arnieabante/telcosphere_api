<?php

namespace App\Interfaces;

use App\Models\Billing;

interface BillingInterface
{
    public function getName(): string;
    public function getClients(array $data): object;
    public function generateBillingItems(Billing $billing, array $data): array;
}