<?php

namespace App\Interfaces;

interface ExpenseInterface
{
    public function getTotals(array $filters = []): array;
}
