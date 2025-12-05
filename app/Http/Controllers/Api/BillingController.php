<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingRequest\ReplaceBillingRequest;
use App\Http\Requests\Api\BillingRequest\UpdateBillingRequest;
use App\Http\Resources\Api\BillingResource;
use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Client;
use App\Services\InvoiceService;
use App\Traits\ApiResponses;
use App\Traits\BillingTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BillingController extends ApiController
{
    use ApiResponses, BillingTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Billing::query()
            ->with('client')
            ->where('is_active', '=', '1');

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
    public function store(Request $request, InvoiceService $service)
    {
        /**
         * Request sample (for each Client)
         * {
         *  billing: {
         *      billingType: 1, // Monthly Subscription
         *      billingCategory: 1,
         *      billingDate: '2025-12-05',
         *      billingCutoff: '2025-11-30',
         *      disconnectionDate: '2025-12-10',
         *      billingRemarks: 'Test Billing',
         *      billingStatus: 'Pending'
         *      billingItems: [
         *          {
         *              // billingItemName: 'Monthly Subscription'
         *              billingItemQuantity: 1,
         *              billingItemPrice: 1000,
         *              billingItemAmount 1000,
         *              billingItemRemark: 'Test Billing Item 1'
         *          }
         *      ]
         *  }
         * }
         * 
         */
        $attributes = $request->input('billing');

        // get clients with the same billing category/cycle
        $clients = Client::where('billing_category_id', $attributes['billingCategory'])
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
            $invoice = $service->generateInvoice();
            $billing = Billing::create([
                'client_id' => $client->id, 
                'invoice_number' => $invoice->invoice_number,
                'billing_date' => $attributes['billingDate'],
                'billing_remarks' => $attributes['billingRemarks'],
                'billing_total' => 0.00, // update base on total amt in BillingItems
                'billing_status' => $attributes['billingStatus'],
                'billing_cutoff' => $attributes['billingCutoff'],
                'disconnection_date' => $attributes['disconnectionDate']
            ]);

            // create Billing Items
            $billingItems = $this->generateBillingIems(
                $attributes['billingItems'],
                $attributes['billingType'],
                $client
            );

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

        return $this->ok('Billings are created for affected clients');
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
