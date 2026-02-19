<?php

namespace App\Services;

use App\Interfaces\ExpenseInterface;
use App\Models\ExpenseItem;
use Carbon\Carbon;

class ExpenseService implements ExpenseInterface
{
    public function getTotals(?string $dateFilter = null): array
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $expense = ExpenseItem::where('is_active', 1)
            ->whereHas('expense');

        // Current month total
        $currentMonthTotal = (clone $expense)
            ->whereHas('expense', fn ($q) =>
                $q->whereBetween('expense_date', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ])
            )
            ->sum('amount');

        // Previous month total
        $previousMonthTotal = (clone $expense)
            ->whereHas('expense', fn ($q) =>
                $q->whereBetween('expense_date', [
                    $now->copy()->subMonth()->startOfMonth(),
                    $now->copy()->subMonth()->endOfMonth(),
                ])
            )
            ->sum('amount');

        // Growth percentage
        $expenseGrowth = $previousMonthTotal == 0
            ? ($currentMonthTotal > 0 ? 100.0 : 0.0)
            : round(
                (($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal) * 100,
                2
            );

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
                        $now->copy()->subDays(6)->startOfDay(),
                        $now->copy()->endOfDay()
                    ])
                )
                ->sum('amount'),

            'last_30_days' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereBetween('expense_date', [
                        $now->copy()->subDays(29)->startOfDay(),
                        $now->copy()->endOfDay()
                    ])
                )
                ->sum('amount'),

            'current_month' => $currentMonthTotal,
            'previous_month' => $previousMonthTotal,

            'expenses_growth' => $expenseGrowth,

            'current_year' => (clone $expense)
                ->whereHas('expense', fn ($q) =>
                    $q->whereYear('expense_date', $now->year)
                )
                ->sum('amount'),

            'total' => (clone $expense)->sum('amount'),
        ];
    }

    public function applyStatusFilter($query, ?string $dateFilter)
    {
        $now = Carbon::now();

        if (!$dateFilter) {
            return $query;
        }

        return $query->whereHas('expense', function ($q) use ($dateFilter, $now) {

            switch ($dateFilter) {

                case 'today':
                    $q->whereDate('expense_date', Carbon::today());
                    break;

                case 'yesterday':
                    $q->whereDate('expense_date', Carbon::yesterday());
                    break;

                case 'weeks':
                    $q->whereBetween('expense_date', [
                        $now->copy()->subDays(6)->startOfDay(),
                        $now->copy()->endOfDay()
                    ]);
                    break;

                case 'month':
                    $q->whereMonth('expense_date', $now->month)
                    ->whereYear('expense_date', $now->year);
                    break;

                case 'year':
                    $q->whereYear('expense_date', $now->year);
                    break;
            }
        });
    }
}
