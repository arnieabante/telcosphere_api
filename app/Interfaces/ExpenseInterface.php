<?php

namespace App\Interfaces;

interface ExpenseInterface
{
    public function getTotals(?string $dateFilter = null): array;
}
