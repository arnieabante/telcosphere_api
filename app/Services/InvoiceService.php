<?php 

namespace App\Services;

use App\Models\Invoice;
use App\Models\Site;
use Exception;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    private $site;
    private $prefix;
    private $suffix;

    public function __construct() 
    {
        $this->site = Site::select([
                'id',
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
        if ($maxDate && 
            (int) date('y') > (int) date('y', strtotime($maxDate))
        ) {
            $this->suffix = 0;
        } else { 
            $this->suffix = ((int) $this->site['invoice_id_yy_last_count']) + 1;
        }
    }

    public function generateInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoiceNumber = $invoice->formatInvoiceNumber(
            $this->prefix, 
            $this->suffix
        );

        // check if invoice number exists on the same site id 
        $exists = Invoice::where('site_id', $this->site['id'])
            ->where('invoice_number', $invoiceNumber)
            ->exists();

        if (!$exists) {
            $newInvoice = Invoice::create([
                'site_id' => $this->site['id'],
                'invoice_number' => $invoiceNumber,
            ]);

            // increment last_count flag
            Site::where('id', $this->site['id'])
                ->update([
                    'invoice_id_yy_last_count' => $this->suffix
                ]);
            
            return $newInvoice;
            
        } else {
            throw new Exception(
                'Invoice Number already exists for the current Site ID.'
            );
        }
    }
}