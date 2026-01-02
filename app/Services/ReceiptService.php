<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    /**
     * Generate a new receipt with a unique number.
     *
     * Format: receipt_YY + zero-padded number (e.g., 25000001)
     * Receipt numbers reset yearly.
     *
     * @return Receipt
     */
    public function generateReceipt(): Receipt
    {
        $currentYear = date('y');

        $lastReceipt = Receipt::where('receipt_YY', $currentYear)
            ->whereNotNull('receipt_last_YY_no')
            ->orderBy('receipt_last_YY_no', 'desc')
            ->lockForUpdate()
            ->first();

        $nextNumber = $lastReceipt
            ? $lastReceipt->receipt_last_YY_no + 1
            : 1;

        return Receipt::create([
            'receipt_YY' => $currentYear,
            'receipt_last_YY_no' => $nextNumber,
            'receipt_no' => $currentYear . str_pad($nextNumber, 6, '0', STR_PAD_LEFT),
        ]);
    }

}
