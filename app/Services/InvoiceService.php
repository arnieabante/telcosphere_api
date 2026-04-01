<?php 

namespace App\Services;

use App\Models\Invoice;
use App\Models\Site;
use Exception;

class InvoiceService
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
        $maxDate = Invoice::max('created_at');
        if ($maxDate && 
            (int) date('y') > (int) date('y', strtotime($maxDate))
        ) {
            return '0';
        } else { 
            return $count + 1;
        }
    }

    public function generateInvoice(): Invoice
    {
        $site = Site::select([
                'id',
                'invoice_id_pattern',
                'invoice_id_yy_last_count'
            ])
            ->where('id', auth()->user()->site_id)
            ->first();

        $prefix = $this->getPrefix($site['invoice_id_pattern']);
        $suffix = $this->getSuffix($site['invoice_id_yy_last_count']);
        
        $invoice = new Invoice();
        $invoiceNumber = $invoice->formatInvoiceNumber($prefix, $suffix);

        // check if invoice number exists on the same site id 
        $exists = Invoice::where('site_id', $site['id'])
            ->where('invoice_number', $invoiceNumber)
            ->exists();

        if (!$exists) {
            $newInvoice = Invoice::create([
                'site_id' => $site['id'],
                'invoice_number' => $invoiceNumber,
            ]);

            // update last_count flag
            Site::where('id', $site['id'])
                ->update([
                    'invoice_id_yy_last_count' => $suffix
                ]);
            
            return $newInvoice;

        } else {
            throw new Exception(
                'Failed to create billing. Invoice Number already exists for the current Site ID.'
            );
        }
    }
}