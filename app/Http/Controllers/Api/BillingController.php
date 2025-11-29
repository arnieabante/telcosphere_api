<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingRequest\ReplaceBillingRequest;
use App\Http\Requests\Api\BillingRequest\StoreBillingRequest;
use App\Http\Requests\Api\BillingRequest\UpdateBillingRequest;
use App\Http\Resources\Api\BillingResource;
use App\Http\Resources\Api\ClientResource;
use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\BillingItem;
use App\Models\Client;
use App\Models\Internetplan;
use App\Traits\ApiResponses;
use DateTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BillingController extends ApiController
{
    use ApiResponses;

    CONST BILLING_TYPE_1 = 'Monthly Subscription';
    CONST BILLING_TYPE_2 = 'Installation Fee';
    CONST BILLING_TYPE_3 = 'Repair Fee';
    CONST BILLING_TYPE_4 = 'Others';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Billing::query()->where('is_active', '=', '1');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('billing_date', 'like', "%{$search}%")
                    ->orWhere('billing_remarks', 'like', "%{$search}%")
                    ->orWhere('billing_total', 'like', "%{$search}%")
                    ->orWhere('billing_status', 'like', "%{$search}%");
            });
        }

        $billing = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return BillingResource::collection($billing);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(StoreBillingRequest $request)
    public function store(Request $request)
    {
        try {
            /**
             * Request sample (for each Client)
             * {
             *  billing: {
             *      billingType: 1, // Monthly Subscription
             *      billingCategory: 1, // 15th
             *      billingDate: '2025-11-25',
             *      billingRemarks: 'Test Billing',
             *      billingStatus: 'Pending'
             *      billingItems: [
             *          {
             *              // billingItemName: 'Monthly Subscription'
             *              billingItemQuantity: 1
             *              billingItemRemark: 'Test Billing Item 1'
             *              billingItemAmount: 1000 // input base on selected subscription
             *              billingItemTotal: 1000 // Qty * Amt
             *          }
             *      ]
             *  }
             * }
             * 
             */
            $attributes = $request->input('billing');

            // create Billing for all clients under the same billing category/cycle
            $clients = Client::where('billing_category_id', $attributes['billingCategory'])
                ->get(['id', 'billing_category_id', 'internet_plan_id', 'prorate_fee', 'prorate_fee_status', 'prorate_end_date']);
            
            foreach ($clients as $client) {
                // create Billing
                $currentBalance = $this->getCurrentBalance($client);
                $newBilling = Billing::create([
                    'client_id' => $client->id, 
                    'billing_date' => $attributes['billingDate'],
                    'billing_remarks' => $attributes['billingRemarks'],
                    'billing_total' => $currentBalance,
                    'billing_status' => $attributes['billingStatus'],
                ]);

                // create Billing Items
                $item = new BillingItem;
                foreach ($attributes['billingItems'] as $attribute) {
                    $itemNameConst = 'self::BILLING_TYPE_' . $attributes['billingType'];

                    // applicable to Monthly Subscription only
                    $itemAmount = $this->getSubscriptionRate($client->internet_plan_id);
                    $itemTotal = floatVal($itemAmount) * $attribute['billingItemQuantity'];

                    $item->create([
                        'billing_id' => $newBilling->id,
                        'billing_item_name' => constant($itemNameConst),
                        'billing_item_quantity' => $attribute['billingItemQuantity'],
                        'billing_item_remark' => $attribute['billingItemRemark'],
                        'billing_item_amount' => $itemAmount,
                        'billing_item_total' => $itemTotal,
                        'billing_status' => 'Pending'
                    ]);
                }

                // update Client previous balance
                Client::where('id', $client->id)
                    ->update([
                        'balance_from_prev_billing' => $currentBalance
                    ]);
            }
            
            // return ClientResource::collection(); // there are multiple client models
            return $this->ok('Billings are created for affected clients');

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Billing.', 401);
        }
    }

    public function getPreviousBalance($clientId)
    {
        $billings = Billing::where('client_id', $clientId)
            ->where('billing_status', 'Pending')
            ->get(['billing_total']);

        $previousBalance = 0.00;
        foreach ($billings as $billing) {
            $previousBalance += floatVal($billing['billing_total']);
        }

        return round($previousBalance, 2);
    }

    public function getSubscriptionRate($plan)
    {
        $plan = Internetplan::select(['monthly_subscription'])
            ->where('id', $plan)
            ->first();

        return round(floatVal($plan->monthly_subscription), 2);

    }

    public function getCurrentBalance($client)
    {
        $currentBalance = 0.00;

        if ($client->prorate_fee_status === 'Pending') {
            $proratedPrevious = (int) $client->prorate_fee;
            $proratedCurrent = $this->calculateProratedCurrent($client);
            $currentBalance = $this->getPreviousBalance($client->id) + ($proratedPrevious + $proratedCurrent);
        } else {
            $currentBalance = $this->getPreviousBalance($client->id) + 
                $this->getSubscriptionRate($client->internet_plan_id);
        }

        return round($currentBalance, 2);
    }

    public function calculateProratedCurrent($client)
    {
        $proratedCurrent = 0.00;

        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id);
        $daysOfCurrentMonth = date('t');
        $dailyRate = $monthlyRate / $daysOfCurrentMonth;
    
        $billing = BillingCategory::select(['date_cycle'])
            ->where('id', $client->billing_category_id)
            ->first();

        $billingCycleStart = date('Y-m-' . $billing->date_cycle); // how about FEB if cycle is 30?
        $billingCycleEnd = new DateTime($billingCycleStart)->modify('+1 month')->format('Y-m-d');

        $startProratedCurrent = new DateTime(date('Y-m-d', strtotime($client->prorate_end_date)));
        $endProratedCurrent = new DateTime($billingCycleEnd);
        $interval = $startProratedCurrent->diff($endProratedCurrent);

        $proratedCurrent += $dailyRate * (int) $interval->days;

        return round($proratedCurrent, 2);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $billing = Billing::with('billingItems')->where('uuid', $uuid)->firstOrFail();
            return new BillingResource($billing);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Billing.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillingRequest $request, string $uuid)
    {
        try {
            $billing = Billing::where('uuid', $uuid)->firstOrFail();
            $affected = $billing->update($request->mappedAttributes());
            
            return new BillingResource($billing);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Billing.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceBillingRequest $request, string $uuid)
    {
        try {
            $billing = Billing::where('uuid', $uuid)->firstOrFail();
            $affected = $billing->update($request->mappedAttributes());
            
            return new BillingResource($billing);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Billing.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $billing = Billing::where('uuid', $uuid)->firstOrFail();
            $affected = $billing->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Billing.', 401);
        }
    }
}
