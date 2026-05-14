<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentRequest\ReplacePaymentRequest;
use App\Http\Requests\Api\PaymentRequest\StorePaymentRequest;
use App\Http\Requests\Api\PaymentRequest\UpdatePaymentRequest;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Client;
use App\Services\DashboardService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\DB;

class PaymentController extends ApiController
{
    use ApiResponses;

    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, DashboardService $service)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $statusFilter = $request->get('status');
        $clientUuid = $request->get('client_id');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Payment::with([
            'client',
            'collectedBy',
            'paymentItems' => function ($q) {
                $q->where('is_active', 1);
            }
        ])->where('is_active', 1);

        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('collection_date', [$from, $to]);
        }

        if (!empty($clientUuid)) {
            $client = \App\Models\Client::where('uuid', $clientUuid)->first();
            if ($client) {
                $query->where('client_id', $client->id);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                ->orWhere('collection_date', 'like', "%{$search}%")
                ->orWhere('amount_paid', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                })
                ->orWhereHas('collectedBy', function ($userQuery) use ($search) {
                    $userQuery->where('fullname', 'like', "%{$search}%");
                });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
                 ->paginate($perPage)
                 ->appends($request->only(['status', 'search', 'per_page']));

        $monthlyProfit = $service->getMonthlyProfit();
        $profitGrowth = $service->getMonthlyProfitGrowth();

        return PaymentResource::collection($payments)
        ->additional([
                'meta' => [
                    'monthly_profit' => $monthlyProfit,
                    'profit_growth' => $profitGrowth
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                $receipt = $this->receiptService->generateReceipt();
                $attributes = array_merge($request->mappedAttributes(), [
                    'receipt_no' => $receipt->receipt_number,
                ]);

                $payment = Payment::create($attributes);
                $affectedBillingIds = [];

                if ($request->has('collectionItems')) {
                    foreach ($request->collectionItems as $item) {

                        $amountPaid = floatval($item['amount_paid']);
                        // 🔹 Insert Payment Item
                        PaymentItem::create([
                            'payment_id'       => $payment->id,
                            'billing_item_id'  => $item['billing_item_id'],
                            'particulars'      => $item['particulars'] ?? null,
                            'amount'           => $item['amount'],
                            'amount_paid'      => $amountPaid,
                            'amount_balance'   => $item['amount_balance'], // optional (can remove if not needed)
                        ]);
                        // 🔹 Update Billing Item safely
                        $billingItem = BillingItem::find($item['billing_item_id']);

                        if ($billingItem) {
                            $billingItem->update([
                                'billing_item_offset' => DB::raw('billing_item_offset + ' . $amountPaid),
                                'billing_item_balance' => DB::raw(
                                    'GREATEST(billing_item_balance - ' . $amountPaid . ', 0)'
                                ),
                            ]);
                            // 🔹 Refresh and update status based on DB value
                            $billingItem->refresh();
                            $billingItem->update([
                                'billing_status' => $billingItem->billing_item_balance > 0 ? 'Partial' : 'Paid',
                            ]);
                            // collect affected billing IDs
                            $affectedBillingIds[] = $billingItem->billing_id;
                        }
                    }
                }

                // 🔹 Recalculate Billing totals & status AFTER loop
                $billingIds = array_unique($affectedBillingIds);

                foreach ($billingIds as $billingId) {
                    $billing = Billing::find($billingId);

                    if ($billing) {
                        $totalOffset = $billing->billingItems()
                            ->where('is_active', 1)
                            ->sum('billing_item_offset');

                        $totalBalance = $billing->billingItems()
                            ->where('is_active', 1)
                            ->sum('billing_item_balance');

                        $billing->update([
                            'billing_offset' => $totalOffset,
                            'billing_balance' => $totalBalance,
                            'billing_status' => $totalBalance > 0 ? 'Partial' : 'Paid',
                        ]);
                    }
                }

                // 🔹 Update Client balances safely
                $client = Client::find($request['clientId']);

                if ($client) {
                    $amountPaid = floatval($request['amountPaid']);

                    $client->update([
                        'balance_from_prev_billing' => DB::raw(
                            'GREATEST(balance_from_prev_billing - ' . $amountPaid . ', 0)'
                        ),
                        'current_balance' => DB::raw(
                            'GREATEST(current_balance - ' . $amountPaid . ', 0)'
                        )
                    ]);
                }

                return new PaymentResource($payment);
            });

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Payment.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $payment = Payment::with(['client' , 'collectedBy'])->where('uuid', $uuid)->firstOrFail();
            return new PaymentResource($payment);


        } catch (ModelNotFoundException $ex) {
            return $this->error('Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Payment.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, string $uuid)
    {
        try {
            return DB::transaction(function () use ($request, $uuid) {

                $payment = Payment::with('paymentItems')
                    ->where('uuid', $uuid)
                    ->firstOrFail();

                $payment->update($request->mappedAttributes());

                // SOFT DELETE / DEACTIVATE PAYMENT
                if ($request->has('isActive') && intval($request->isActive) === 0) {

                    foreach ($payment->paymentItems as $paymentItem) {
                        $amountPaid = floatval($paymentItem->amount_paid);


                        // REVERSE BILLING ITEM
                        $billingItem = BillingItem::find($paymentItem->billing_item_id);

                        if ($billingItem) {
                            // revert balance
                            $billingItem->billing_item_balance =
                                floatval($billingItem->billing_item_balance) + $amountPaid;
                            // revert offset
                            $billingItem->billing_item_offset =
                                floatval($billingItem->billing_item_offset) - $amountPaid;
                            // prevent negative offset
                            if ($billingItem->billing_item_offset < 0) {
                                $billingItem->billing_item_offset = 0;
                            }

                            // BILLING ITEM STATUS

                            if ($billingItem->billing_item_balance <= 0) {

                                $billingItem->billing_status = 'Paid';

                            } elseif (
                                $billingItem->billing_item_balance <
                                floatval($billingItem->billing_item_amount)
                            ) {

                                $billingItem->billing_status = 'Partial';

                            } else {

                                $billingItem->billing_status = 'Pending';
                            }

                            $billingItem->save();

                            // REVERSE BILLING

                            $billing = Billing::find($billingItem->billing_id);

                            if ($billing) {
                                // revert billing balance
                                $billing->billing_balance =
                                    floatval($billing->billing_balance) + $amountPaid;
                                // revert billing offset
                                $billing->billing_offset =
                                    floatval($billing->billing_offset) - $amountPaid;
                                // prevent negative offset
                                if ($billing->billing_offset < 0) {
                                    $billing->billing_offset = 0;
                                }

                                // BILLING STATUS
                                if ($billing->billing_balance <= 0) {

                                    $billing->billing_status = 'Paid';

                                } elseif (
                                    $billing->billing_balance <
                                    floatval($billing->billing_amount)
                                ) {

                                    $billing->billing_status = 'Partial';

                                } else {

                                    $billing->billing_status = 'Pending';
                                }

                                $billing->save();
                            }
                        }

                        // DEACTIVATE PAYMENT ITEMS
                        $paymentItem->is_active = 0;
                        $paymentItem->save();
                    }

                    // UPDATE CLIENT BALANCE
                    $client = Client::find($payment->client_id);

                    if ($client) {

                        $totalReversedAmount =
                            floatval($payment->paymentItems->sum('amount_paid'));

                        $client->current_balance += $totalReversedAmount;

                        $client->save();
                    }

                    // DEACTIVATE PAYMENT
                    $payment->is_active = 0;
                    $payment->save();

                    return new PaymentResource($payment->fresh());
                }

                // NORMAL UPDATE
                if ($request->has('collectionItems')) {
                    // store old payment total
                    $oldPaymentTotal = floatval(
                        $payment->paymentItems->sum('amount_paid')
                    );

                    foreach ($request->collectionItems as $item) {

                        $paymentItem = PaymentItem::find($item['id']);

                        if (!$paymentItem) {
                            continue;
                        }

                        $oldAmountPaid = floatval($paymentItem->amount_paid);
                        $newAmountPaid = floatval($item['amount_paid']);
                        $difference = $newAmountPaid - $oldAmountPaid;

                        // UPDATE PAYMENT ITEM
                        $paymentItem->update([
                            'amount'         => $item['amount'],
                            'amount_paid'    => $newAmountPaid,
                            'amount_balance' => $item['amount_balance'],
                        ]);

                        // only update related tables if changed
                        if ($difference != 0) {

                            // UPDATE BILLING ITEM
                            $billingItem = BillingItem::find($paymentItem->billing_item_id);

                            if ($billingItem) {

                                $billingItem->billing_item_offset += $difference;
                                $billingItem->billing_item_balance -= $difference;

                                // prevent negative values
                                if ($billingItem->billing_item_offset < 0) {
                                    $billingItem->billing_item_offset = 0;
                                }

                                if ($billingItem->billing_item_balance < 0) {
                                    $billingItem->billing_item_balance = 0;
                                }

                                // STATUS
                                if ($billingItem->billing_item_balance <= 0) {

                                    $billingItem->billing_status = 'Paid';

                                } elseif (
                                    $billingItem->billing_item_balance <
                                    floatval($billingItem->billing_item_amount)
                                ) {

                                    $billingItem->billing_status = 'Partial';

                                } else {

                                    $billingItem->billing_status = 'Pending';
                                }

                                $billingItem->save();

                                // UPDATE BILLING
                                $billing = Billing::find($billingItem->billing_id);

                                if ($billing) {

                                    $billing->billing_offset += $difference;
                                    $billing->billing_balance -= $difference;

                                    // prevent negative values
                                    if ($billing->billing_offset < 0) {
                                        $billing->billing_offset = 0;
                                    }

                                    if ($billing->billing_balance < 0) {
                                        $billing->billing_balance = 0;
                                    }

                                    // BILLING STATUS
                                    if ($billing->billing_offset <= 0) {
                                        $billing->billing_status = 'Pending';
                                    } elseif ($billing->billing_balance <= 0) {
                                        $billing->billing_status = 'Paid';
                                    } else {
                                        $billing->billing_status = 'Partial';
                                    }

                                    $billing->save();
                                }
                            }
                        }
                    }

                    // UPDATE PAYMENT
                    $payment->update($request->mappedAttributes());
                    // RELOAD PAYMENT ITEMS
                    $payment->load('paymentItems');
                    // COMPUTE NEW TOTAL
                    $newPaymentTotal = floatval(
                        $payment->paymentItems->sum('amount_paid')
                    );
                    // DIFFERENCE
                    $clientDifference = $newPaymentTotal - $oldPaymentTotal;
                    // UPDATE CLIENT
                    $client = Client::find($payment->client_id);

                    if ($client && $clientDifference != 0) {

                        // CURRENT BALANCE
                        $client->current_balance =
                            floatval($client->current_balance) - $clientDifference;

                        // PREVIOUS BILLING BALANCE
                        $client->balance_from_prev_billing =
                            floatval($client->balance_from_prev_billing) - $clientDifference;

                        // PREVENT NEGATIVE
                        if ($client->current_balance < 0) {
                            $client->current_balance = 0;
                        }

                        if ($client->balance_from_prev_billing < 0) {
                            $client->balance_from_prev_billing = 0;
                        }

                        $client->save();
                    }
                }

                return new PaymentResource($payment->fresh());
            });

        } catch (AuthorizationException $ex) {

            return $this->error(
                'You are not authorized to update a Payment.',
                401
            );
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplacePaymentRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Payment::class);

            $payment = Payment::where('uuid', $uuid)->firstOrFail();
            $affected = $payment->update($request->mappedAttributes());

            return new PaymentResource($payment);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Payment.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $payment = Payment::where('uuid', $uuid)->firstOrFail();
            $affected = $payment->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Payment.', 401);
        }
    }
}
