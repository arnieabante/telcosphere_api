<?php 

namespace App\Services;

use App\Models\Invoice;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    private $site;
    private $prefix;
    private $suffix;

    public function __construct() 
    {
        $this->site = Site::select([
                'invoice_id_pattern',
                'invoice_id_yy_last_count'
            ])
            ->where('id', auth()->user()->site_id)
            ->first();

        $this->setPrefix();
        $this->setSuffix();
    }

    private function setPrefix(): Void
    {
        $this->prefix = strlen(trim(
                $this->site['invoice_id_pattern']
            )) > 0 ?
            $this->site['invoice_id_pattern'] . '-' :
            '';
    }

    private function setSuffix(): Void 
    {
        // if new year reset count
        $maxDate = Invoice::max('created_at');
        $site = Site::where('id', auth()->user()->site_id)->first();

        if ($maxDate && 
            (int) date('y') > (int) date('y', strtotime($maxDate))
        ) {
            $site->update([
                'invoice_id_yy_last_count' => 0
            ]);
        }

        $this->suffix = ((int) $site['invoice_id_yy_last_count']) + 1;
        
        // update new count
        $site->update([
            'invoice_id_yy_last_count' => $this->suffix
        ]);
    }

    public function generateInvoice(): Invoice
    {
        return DB::transaction(function () {
            $invoice = Invoice::create();
            $invoiceNumber = $invoice->formatInvoiceNumber(
                $this->prefix, 
                $this->suffix
            );

            $invoice->update([
                'invoice_number' => $invoiceNumber
            ]);

            return $invoice;
        });
    }
}