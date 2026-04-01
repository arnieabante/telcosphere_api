<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class ReceiptService
{

    private $site;
    private $prefix;
    private $suffix;

    public function __construct()
    {
        $this->site = Site::select([
            'receipt_id_pattern',
            'receipt_id_yy_last_count'
        ])
        ->where('id', auth()->user()->site_id)
        ->first();

        $this->setPrefix();
    }

    private function setPrefix(): Void
    {
        $this->prefix = strlen(trim(
                $this->site['receipt_id_pattern']
            )) > 0 ?
            $this->site['receipt_id_pattern'] . '-' :
            '';
    }

    private function setSuffix($site): void
    {
        $maxDate = Receipt::max('created_at');
        $site = Site::find(auth()->user()->site_id);

        $currentYear = date('y');
        $lastYear = (!empty($maxDate) && strtotime($maxDate))
            ? (int) date('y', strtotime($maxDate))
            : null;

        $count = $site->receipt_id_yy_last_count;

        if ($lastYear !== null && $currentYear > $lastYear) {
            $count = 0;
        }

        $this->suffix = $count + 1;

        $site->update([
            'receipt_id_yy_last_count' => $this->suffix
        ]);
    }

    public function generateReceipt(): Receipt
    {
        return DB::transaction(function () {
            // lock row to prevent duplicate increments
            $site = Site::find(auth()->user()->site_id)
                ->lockForUpdate()
                ->first();

            $this->setSuffix($site);

            $receipt = Receipt::create();
            $receiptNumber = $receipt->formatReceiptNumber(
                $this->prefix,
                $this->suffix
            );

            $receipt->update([
                'receipt_no' => $receiptNumber
            ]);

            return $receipt;
        });
    }
}
