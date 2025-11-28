<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingRequest\ReplaceBillingRequest;
use App\Http\Requests\Api\BillingRequest\StoreBillingRequest;
use App\Http\Requests\Api\BillingRequest\UpdateBillingRequest;
use App\Http\Resources\Api\BillingResource;
use App\Models\Billing;
use App\Models\Client;
use App\Models\Internetplan;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BillingController extends ApiController
{
    use ApiResponses;

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
    public function store(StoreBillingRequest $request)
    {
        try {
            $attributes = $request->mappedAttributes();

            // create Billing for all clients under the same billing category/cycle
            $clients = Client::where(
                    'billing_category_id', '=', $attributes['billing_category']
                )
                ->get(['id', 'internet_plan_id']);
            
            foreach ($clients as $client) {
                Billing::create([
                    'client_id' => $client->id, 
                    'billing_date' => $attributes['billing_date'],
                    'billing_remarks' => $attributes['billing_remarks'],
                    'billing_total' => $this->getBalance($client),
                    'biling_status' => $attributes['billing_status'],
                ]);
            }
            
            // return created models(?)

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Billing.', 401);
        }
    }

    public function getBalance($client)
    {
        $previous = Billing::where('client_id', '=', $client->id)
            ->where('billing_status', '=', 'pending')
            ->get(['id', 'billing_status', 'billing_total']);
        
        $plan = Internetplan::where('internet_plan_id', '=', $client->internet_plan_id)
            ->get(['monthly_subscription']);

        if ($client->prorate_fee_status === 'pending') {
            // TODO: calculate pro-rated subscription rates
            $proratedRate = 0.00;
            return round (
                floatVal($previous['billing_total']) + floatVal($proratedRate), 
                2
            );
        } else {
            return round (
                floatVal($previous['billing_total']) + floatVal($plan['monthly_subscription']), 
                2
            );
        }
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
