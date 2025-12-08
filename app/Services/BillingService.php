<?php 

namespace App\Services;

use App\Interfaces\BillingInterface;
use App\Models\Billing;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class BillingService
{
    protected $invoice;

    public function __construct(InvoiceService $invoice) {
        $this->invoice = $invoice;
    }

    public function generateBilling(BillingInterface $billingType, $data)
    {
        // get clients with the same billing category/cycle
        $clients = Client::where('billing_category_id', $data['billingCategory'])
            ->get([
                'id', 
                'billing_category_id', 
                'internet_plan_id', 
                'prorate_fee', 
                'prorate_fee_status', 
                'prorate_end_date',
                'installation_fee'
            ]);
        
        foreach ($clients as $client) {
            // create individual Billing
            $billing = Billing::create([
                'client_id' => $client->id, 
                'invoice_number' => $this->invoice->generateInvoice()->invoice_number,
                // 'billing_date' => $billing['billingDate'],
                'billing_remarks' => $data['billingRemarks'],
                'billing_total' => 0.00, // update base on total amt in BillingItems
                'billing_status' => 'Pending',
                'billing_cutoff' => $data['billingCutoff'],
                'disconnection_date' => $data['disconnectionDate']
            ]);

            // create Billing Items by Billing Type
            $billingItems = $billingType->generateBillingItems($billing, $data['billingItems']);

            if (count($billingItems) > 1)
                $billing->billingItems()->createMany($billingItems);
            else 
                $billing->billingItems()->create($billingItems[0]);

            // update Billing Total
            $latestBilling = Billing::latest()->first();
            $latestBilling->load('billingItems');
            $latestBillingTotal = $latestBilling->billingItems()->sum('billing_item_amount');

            $billing->update([
                'billing_total' => $latestBillingTotal
            ]);

            // update Client Balance
            $latestClientBalance = Billing::where('client_id', $client->id)
                ->where('billing_status', 'pending')
                ->sum('billing_total');

            $billing->client()->update([
                'balance_from_prev_billing' => $latestClientBalance
            ]);
        }
    }
}