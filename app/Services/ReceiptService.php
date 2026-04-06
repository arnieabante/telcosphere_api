<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Site;

class ReceiptService
{
    private function getPrefix($pattern): String 
    {
        return strlen(trim($pattern)) > 0 ?
            $pattern . '-' :
            '';
    }

    private function getSuffix($count): String 
    {
        // if new year, reset count
        $maxDate = Receipt::max('created_at');
        if ($maxDate && 
            (int) date('y') > (int) date('y', strtotime($maxDate))
        ) {
            return '0';
        } else { 
            return $count + 1;
        }
    }

    public function generateReceipt(): Receipt
    {
        $site = Site::select([
                'id',
                'receipt_id_pattern',
                'receipt_id_yy_last_count'
            ])
            ->where('id', auth()->user()->site_id)
            ->first();

        $prefix = $this->getPrefix($site['receipt_id_pattern']);
        $suffix = $this->getSuffix($site['receipt_id_yy_last_count']);
        
        $receipt = new Receipt();
        $receiptNumber = $receipt->formatReceiptNumber($prefix, $suffix);

        // check if invoice number exists on the same site id 
        $exists = Receipt::where('site_id', $site['id'])
            ->where('receipt_number', $receiptNumber)
            ->exists();

        if (!$exists) {
            $newReceipt = Receipt::create([
                'site_id' => $site['id'],
                'receipt_number' => $receiptNumber,
            ]);

            // update last_count flag
            Site::where('id', $site['id'])
                ->update([
                    'receipt_id_yy_last_count' => $suffix
                ]);
            
            return $newReceipt;

        } else {
            throw new Exception(
                'Failed to create payment. Receipt Number already exists for the current Site ID.'
            );
        }
    }
}
