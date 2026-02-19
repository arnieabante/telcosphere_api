<?php

namespace App\Interfaces;

interface ExpenseInterface
{
    public function getTotals(?string $status = null): array;
}
