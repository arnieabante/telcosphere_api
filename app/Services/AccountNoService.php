<?php

namespace App\Services;

use Exception;
use App\Models\Site;
use App\Models\Client;

class AccountNoService
{
    private function getPrefix(?string $pattern): string
    {
        return !empty(trim($pattern))
            ? trim($pattern) . '-'
            : '';
    }

    private function getNextCount(int $count): int
    {
        return $count + 1;
    }

    private function formatAccountNo(string $prefix, int $count): string
    {
        return $prefix . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function generateAccountNo(): string
    {
        $site = Site::select([
                'id',
                'account_number_pattern',
                'account_no_last_count'
            ])
            ->where('id', auth()->user()->site_id)
            ->first();

        if (!$site) {
            throw new Exception('Site not found.');
        }

        $prefix = $this->getPrefix(
            $site->account_number_pattern
        );

        $nextCount = $this->getNextCount(
            $site->account_no_last_count
        );

        $accountNo = $this->formatAccountNo(
            $prefix,
            $nextCount
        );

        $exists = Client::where('site_id', $site->id)
            ->where('account_no', $accountNo)
            ->exists();

        if ($exists) {
            throw new Exception(
                'Failed to generate Account Number. Account Number already exists.'
            );
        }

        Site::where('id', $site->id)
            ->update([
                'account_no_last_count' => $nextCount
            ]);

        return $accountNo;
    }
}