<?php 

namespace App\Services;

use App\Interfaces\BillingInterface;
use App\Models\Billing;
use Exception;
use App\Http\Resources\Api\BillingResource;
use App\Libraries\Billing\MonthlySubscription;
use App\Models\BillingCategory;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class BillingService
{
    const STATUS_PENDING = 'Pending';
    const STATUS_PARTIAL = 'Partial';
    const STATUS_BILLED = 'Billed';
    const STATUS_PAID = 'Paid';

    protected $invoice;

    public function __construct(InvoiceService $invoice) 
    {
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
                'billing_type' => $data['billingType'],
                'billing_date' => date('Y-m-d H:i:s'), // current date
                'billing_description' => NULL, // auto-populate after generating billing items
                'billing_remarks' => $data['billingRemarks'] ?? NULL,
                'billing_total' => 0.00, // update base on total amt in BillingItems
                'billing_offset' => 0.00,
                'billing_balance' => 0.00, // update base on total amt in BillingItems
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

            // update Billing Total/Balance
            $latestBilling = Billing::latest()->first();
            $latestBilling->load('billingItems');
            $latestBillingTotal = $latestBilling->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->sum('billing_item_amount');
            $latestBillingBalance = $latestBilling->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->sum('billing_item_balance');

            $billing->update([
                'billing_total' => $latestBillingTotal,
                'billing_balance' => $latestBillingBalance,
                'billing_description' => $billingType->getName()
            ]);

            // update Client Balance
            $latestClientBalance = Billing::where('client_id', $client->id)
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->sum('billing_balance');

            $billing->client()->update([
                'balance_from_prev_billing' => $latestClientBalance,
                'prorate_fee_status' => self::STATUS_BILLED,
                'last_auto_billing_date' => date('Y-m-d H:i:s'), // current date
            ]);
        }
    }

    public function updateBilling($uuid, $data)
    {
        $billing = Billing::where('uuid', $uuid)->firstOrFail();

        // activate / deactivate
        if (isset($data['isActive'])) {
            $billing->update([
                'is_active' => 0
            ]);

            return new BillingResource($billing);
        }

        // update Billing Items
        foreach ($data['billingItems'] as $item) {
            $billing->billingItems()->updateOrCreate([
                'uuid' => $item['uuid']
            ], [
                'billing_item_name' => $item['category'],
                'billing_item_particulars' => $item['particulars'],
                'billing_item_quantity' => $item['qty'],
                'billing_item_price' => $item['price'],
                'billing_item_amount' => $item['amount'],
                'billing_status' => self::STATUS_PENDING
            ]);
        }

        // update Billing Total/Balance and Billing Details
        $latestBillingTotal = $billing->billingItems()
            ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
            ->sum('billing_item_amount');
        $latestBillingBalance = $billing->billingItems()
            ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
            ->sum('billing_item_balance');
            
        $billing->update([
            'billing_total' => $latestBillingTotal,
            'billing_balance' => $latestBillingBalance,
            'billing_type' => $data['billingType'],
            'billing_remarks' => $data['billingRemarks'],
            'client_id' => $data['clientId']
        ]);

        // update Client Balance and Client Details
        $latestClientBalance = $billing->where('client_id', $data['clientId'])
            ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
            ->sum('billing_balance');

        $billing->client()->update([
            'balance_from_prev_billing' => $latestClientBalance,
            'house_no' => $data['billingDescription']
        ]);

        return new BillingResource($billing);
    }

    public function runAutomatedBilling()
    {
        $billingType = new MonthlySubscription();
        $categories = BillingCategory::select(['id', 'name'])
            ->where('date_cycle', date('d'))
            ->get();

        if (count($categories) < 1)
            throw new Exception('No Category found.');

        foreach ($categories as $category) {
            $remark = "Automated Subscription Billing ({$category['name']})";
            $billingData = [
                'billingCategory' => $category['id'],
                'billingType' => 1,
                'billingRemarks' => $remark,
                'billingItems' => [
                    [
                        'billingItemQuantity' => 1, 
                        'billingItemRemark' => $remark
                    ]
                ]
            ];

            $this->generateBilling($billingType, $billingData);
        }
    }
}