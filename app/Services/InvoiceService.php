<?php 

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoice(): Invoice
    {
        return DB::transaction(function () {
            $invoice = Invoice::create();
            $invoiceNumber = $invoice->formatInvoiceNumber();

            $invoice->update([
                'invoice_number' => $invoiceNumber
            ]);

            return $invoice;
        });
    }
}