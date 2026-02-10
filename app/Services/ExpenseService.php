<?php

namespace App\Services;

use App\Interfaces\ExpenseInterface;
use App\Models\ExpenseItem;
use Carbon\Carbon;

class ExpenseService implements ExpenseInterface
{
    public function getTotals(array $filters = []): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $expense = ExpenseItem::where('is_active', 1)
            ->whereHas('expense');

        return [
            'today' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereDate('expense_date', $today)
                )
                ->sum('amount'),

            'yesterday' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereDate('expense_date', $yesterday)
                )
                ->sum('amount'),

            'last_7_days' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereBetween('expense_date', [
                        Carbon::now()->subDays(6)->startOfDay(),
                        Carbon::now()->endOfDay()
                    ])
                )
                ->sum('amount'),

            'last_30_days' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereBetween('expense_date', [
                        Carbon::now()->subDays(29)->startOfDay(),
                        Carbon::now()->endOfDay()
                    ])
                )
                ->sum('amount'),

            'current_year' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereYear('expense_date', Carbon::now()->year)
                )
                ->sum('amount'),

            'total' => (clone $expense)->sum('amount'),
        ];
    }
}
