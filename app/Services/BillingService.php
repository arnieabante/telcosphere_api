<?php 

namespace App\Services;

use App\Interfaces\BillingInterface;
use App\Models\Billing;
use Exception;

class BillingService
{
    const STATUS_PENDING = 'Pending';
    const STATUS_PAID = 'Paid';
    const STATUS_BILLED = 'Billed';

    protected $invoice;

    public function __construct(InvoiceService $invoice) {
        $this->invoice = $invoice;
    }

    public function generateBilling(BillingInterface $billingType, $data)
    {
        $clients = $billingType->getClients($data);

        if (count($clients) < 1)
            throw new Exception('No Client found.');

        foreach ($clients as $client) {
            // create individual Billing
            $billing = Billing::create([
                'client_id' => $client->id, 
                'invoice_number' => $this->invoice->generateInvoice()->invoice_number,
                'billing_date' => date('Y-m-d H:i:s'), // current date
                'billing_remarks' => $data['billingRemarks'] ?? NULL,
                'billing_total' => 0.00, // update base on total amt in BillingItems
                'billing_status' => self::STATUS_PENDING,
                'billing_cutoff' => $data['billingCutoff'] ?? NULL,
                'disconnection_date' => $data['disconnectionDate'] ?? NULL
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
                ->where('billing_status', self::STATUS_PENDING)
                ->sum('billing_total');

            $billing->client()->update([
                'balance_from_prev_billing' => $latestClientBalance,
                'prorate_fee_status' => self::STATUS_BILLED
            ]);
        }
    }
}