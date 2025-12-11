<?php

namespace App\Interfaces;

use App\Models\Billing;

interface BillingInterface
{
    public function getName();
    // public function getRate();
    public function generateBillingItems(Billing $billing, array $data);
}