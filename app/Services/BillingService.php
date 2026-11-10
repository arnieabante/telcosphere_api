<?php

namespace App\Services;

use App\Interfaces\BillingInterface;
use App\Models\Billing;
use Exception;
use App\Http\Resources\Api\BillingResource;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\AdvancePayments;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    const STATUS_PENDING = 'Pending';
    const STATUS_PARTIAL = 'Partial';
    const STATUS_BILLED = 'Billed';
    const STATUS_PAID = 'Paid';
    const BILLING_TYPE_ADJUSTMENT = 5;

    protected $invoice;
    protected $smsNotifications;

    public function __construct(InvoiceService $invoice, SmsNotificationService $smsNotifications)
    {
        $this->invoice = $invoice;
        $this->smsNotifications = $smsNotifications;
    }

    public function generateBilling(BillingInterface $billingType, $data)
    {
        $clients = $billingType->getClients($data);

        if (count($clients) < 1)
            throw new Exception('No Client found.');

        foreach ($clients as $client) {
            // gather balance if any of pending/partial billing status
            $prevBalance = Billing::where('client_id', $client->id)
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_balance');
            
            // get inputted prev balance in add new client
            if ((int) $prevBalance == 0) {
                $clientPrevBalance = Client::where('id', $client->id)
                    ->get(['current_balance']);

                $prevBalance += $clientPrevBalance[0]['current_balance'];
            }

            // create new Billing
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
                'disconnection_date' => $data['disconnectionDate'] ?? NULL,
                'due_date' => $data['billingCutoff'] ?? NULL,
                'balance_from_prev_billing' => floatVal($prevBalance)
            ]);

            // create Billing Items by Billing Type
            $billingItems = $billingType->generateBillingItems($billing, $data['billingItems']);

            if (count($billingItems) > 1)
                $billing->billingItems()->createMany($billingItems);
            else
                $billing->billingItems()->create($billingItems[0]);

            // update Billing Total/Balance
            $latestBilling = Billing::where('id', $billing->id)->first();
            $latestBilling->load('billingItems');
            $latestBillingTotal = $latestBilling->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_item_amount');
            $latestBillingBalance = $latestBilling->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_item_balance');
            
            // less advance payment from the balance and consume it from the client's credit
            DB::transaction(function () use ($billing, $billingType, $data, $latestBillingTotal, $latestBillingBalance) {
                $advancePaymentAmount = (int) $data['billingType'] !== self::BILLING_TYPE_ADJUSTMENT
                    ? $this->applyAdvancePayment($billing, $latestBillingBalance)
                    : 0;
                $billingBalance = round($latestBillingBalance - $advancePaymentAmount, 2);

                $billing->update([
                    'advance_payment' => $advancePaymentAmount,
                    'billing_total' => $latestBillingTotal,
                    'billing_balance' => $billingBalance,
                    'billing_status' => $advancePaymentAmount > 0 && $billingBalance <= 0
                        ? self::STATUS_PAID
                        : self::STATUS_PENDING,
                    'billing_description' => $billingType->getName()
                ]);
            });

            // update Client Balance
            $currentClientBalance = Billing::where('client_id', $client->id)
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_balance');

            $latestDueDate = NULL;

            if ($client->billingCategory && $client->billingCategory->days_to_due_date !== null) {
                $latestDueDate = Carbon::parse($billing->billing_date)
                    ->addDays((int) $client->billingCategory->days_to_due_date)
                    ->toDateString();
            }

            $billing->client()->update([
                'current_balance' => $currentClientBalance,
                'prorate_fee_status' => self::STATUS_BILLED,
                'last_auto_billing_date' => $billing->billing_date,
                'latest_due_date' => $latestDueDate,
                'latest_disconnection_date' => $billing->disconnection_date,
            ]);

            $this->smsNotifications->notifyBillingCreated($billing);
        }
    }

    public function updateBilling($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $billing = Billing::where('uuid', $uuid)->firstOrFail();
            $appliedAdvancePayment = floatVal($billing->advance_payment);

            // the applied advance payment belongs to the billed client
            if ($appliedAdvancePayment > 0 && (int) $data['clientId'] !== (int) $billing->client_id)
                throw new Exception('Cannot change the client of a billing with an applied advance payment.');

            $advancePaymentAmount = $appliedAdvancePayment;

            // update Billing Items
            foreach ($data['billingItems'] as $item) {
                $billingItem = $billing->billingItems()->firstOrNew(['uuid' => $item['uuid']]);
                $billingItemOffset = floatVal($billingItem->billing_item_offset ?? 0);

                // amount lowered below what was offset, release the excess advance payment
                $advancePaymentRelease = min(max($billingItemOffset - $item['amount'], 0), $advancePaymentAmount);
                $billingItemOffset = round($billingItemOffset - $advancePaymentRelease, 2);
                $advancePaymentAmount = round($advancePaymentAmount - $advancePaymentRelease, 2);

                $billingItem->fill([
                    'billing_item_name' => $item['category'],
                    'billing_item_particulars' => $item['particulars'],
                    'billing_item_quantity' => $item['qty'],
                    'billing_item_price' => $item['price'],
                    'billing_item_amount' => $item['amount'],
                    'billing_item_offset' => $billingItemOffset,
                    'billing_item_balance' => $item['amount'] - $billingItemOffset,
                    'billing_status' => self::STATUS_PENDING
                ])->save();
            }

            $this->restoreAdvancePayment($billing, round($appliedAdvancePayment - $advancePaymentAmount, 2));

            // update Billing Total/Balance and Billing Details
            $latestBillingTotal = $billing->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_item_amount');
            // item balances are already net of the advance payment
            $latestBillingBalance = $billing->billingItems()
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_item_balance');

            $billingAttributes = [
                'advance_payment' => $advancePaymentAmount,
                'billing_total' => $latestBillingTotal,
                'billing_balance' => $latestBillingBalance,
                'billing_type' => $data['billingType'],
                'billing_remarks' => $data['billingRemarks'],
                'client_id' => $data['clientId']
            ];

            if ($appliedAdvancePayment > 0) {
                if ($latestBillingBalance <= 0)
                    $billingAttributes['billing_status'] = self::STATUS_PAID;
                else
                    $billingAttributes['billing_status'] = floatVal($billing->billing_offset) > 0
                        ? self::STATUS_PARTIAL
                        : self::STATUS_PENDING;
            }

            $billing->update($billingAttributes);

            // update Client Balance and Client Details
            $currentClientBalance = $billing->where('client_id', $data['clientId'])
                ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                ->where('is_active', 1)
                ->sum('billing_balance');

            $billing->client()->update([
                'current_balance' => $currentClientBalance,
                'house_no' => $data['billingDescription']
            ]);

            return new BillingResource($billing);
        });
    }

    public function deactivateBilling($uuid, $data)
    {
        // only PENDING billing, or PAID purely by advance payment (no collection), can be de-activated
        $billing = Billing::where('uuid', $uuid)
            ->where(function ($query) {
                $query->where('billing_status', self::STATUS_PENDING)
                    ->orWhere(function ($query) {
                        $query->where('billing_status', self::STATUS_PAID)
                            ->where('billing_offset', 0)
                            ->where('advance_payment', '>', 0);
                    });
            })
            ->where('is_active', 1)
            ->first();

        if ($billing) {
            DB::transaction(function () use ($billing) {
                // deactive billing
                $billing->update(['is_active' => 0]);

                // deactivate Billing Items
                $billing->billingItems()->update([
                    'is_active' => 0
                ]);

                // give back the advance payment used by this billing
                $this->restoreAdvancePayment($billing, floatVal($billing->advance_payment));

                // re-calculate Billing Total/Balance and Billing Details
                $latestBillingTotal = $billing->billingItems()
                    ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                    ->where('is_active', 1)
                    ->sum('billing_item_amount');
                $latestBillingBalance = $billing->billingItems()
                    ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                    ->where('is_active', 1)
                    ->sum('billing_item_balance');

                $billing->update([
                    'advance_payment' => 0,
                    'billing_total' => $latestBillingTotal,
                    'billing_balance' => $latestBillingBalance,
                ]);

                // re-calculate Client Balance and Client Details
                $currentClientBalance = $billing->where('client_id', $billing->client_id)
                    ->whereIn('billing_status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
                    ->where('is_active', 1)
                    ->sum('billing_balance');

                $billing->client()->update([
                    'current_balance' => $currentClientBalance
                ]);
            });
        } else
            throw new Exception('Cannot delete Partial or Paid billing.');

        return new BillingResource($billing);
    }

    /**
     * Bill today's cycles for the current user's site, with the same due and
     * disconnection dates the create billing screen works out.
     *
     * @return array<string, string> outcome per billing category name
     */
    public function runAutomatedBilling(BillingInterface $billingType): array
    {
        $today = Carbon::today();

        // a cycle on the 30th or 31st still has to be billed in shorter months
        $cycleDays = $today->isLastOfMonth() ? range($today->day, 31) : [$today->day];

        $categories = BillingCategory::select(['id', 'name', 'days_to_due_date', 'days_to_disconnection_date'])
            ->whereIn('date_cycle', $cycleDays)
            ->where('site_id', auth()->user()->site_id)
            ->where('is_active', 1)
            ->get();

        $outcomes = [];

        foreach ($categories as $category) {
            $remark = "Automated Subscription Billing ({$category['name']})";
            $dueDate = $today->copy()->addDays((int) $category['days_to_due_date']);
            $billingData = [
                'billingCategory' => $category['id'],
                'billingType' => 1,
                'billingRemarks' => $remark,
                'billingCutoff' => $dueDate->toDateString(),
                'disconnectionDate' => $dueDate->copy()->addDays((int) $category['days_to_disconnection_date'])->toDateString(),
                'billingItems' => [
                    [
                        'billingItemQuantity' => 1,
                        'billingItemRemark' => $remark
                    ]
                ]
            ];

            // one category with nobody to bill must not stop the others
            try {
                $this->generateBilling($billingType, $billingData);
                $outcomes[$category['name']] = 'billed';
            } catch (Exception $ex) {
                $outcomes[$category['name']] = $ex->getMessage();
            }
        }

        return $outcomes;
    }

    /**
     * Consume the client's advance payment against the billing items, first item first.
     * Must run inside a transaction. Returns the amount applied, never more than the sub-total.
     */
    protected function applyAdvancePayment(Billing $billing, $subTotal): float
    {
        $advancePayment = AdvancePayments::where('client_id', $billing->client_id)
            ->where('amount', '>', 0)
            ->lockForUpdate()
            ->first();

        if (!$advancePayment)
            return 0;

        $appliedAmount = round(min(floatVal($advancePayment->amount), floatVal($subTotal)), 2);

        if ($appliedAmount <= 0)
            return 0;

        $remaining = $appliedAmount;
        $billingItems = $billing->billingItems()->where('is_active', 1)->orderBy('id')->get();

        foreach ($billingItems as $billingItem) {
            $itemApplied = min($remaining, floatVal($billingItem->billing_item_balance));

            if ($itemApplied <= 0)
                continue;

            $itemBalance = round($billingItem->billing_item_balance - $itemApplied, 2);

            $billingItem->update([
                'billing_item_offset' => round($billingItem->billing_item_offset + $itemApplied, 2),
                'billing_item_balance' => $itemBalance,
                'billing_status' => $itemBalance > 0 ? self::STATUS_PARTIAL : self::STATUS_PAID
            ]);

            $remaining = round($remaining - $itemApplied, 2);
        }

        $advancePayment->update([
            'amount' => round($advancePayment->amount - $appliedAmount, 2)
        ]);

        return $appliedAmount;
    }

    /**
     * Return an applied advance payment to the client's credit. Must run inside a transaction.
     */
    protected function restoreAdvancePayment(Billing $billing, float $amount): void
    {
        if ($amount <= 0)
            return;

        $advancePayment = AdvancePayments::where('client_id', $billing->client_id)
            ->lockForUpdate()
            ->first();

        if ($advancePayment) {
            $advancePayment->update([
                'amount' => round($advancePayment->amount + $amount, 2)
            ]);
        } else {
            AdvancePayments::create([
                'site_id' => $billing->site_id,
                'client_id' => $billing->client_id,
                'amount' => $amount
            ]);
        }
    }
}
