<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingRequest\ReplaceBillingRequest;
use App\Http\Requests\Api\BillingRequest\UpdateBillingRequest;
use App\Http\Resources\Api\BillingResource;
use App\Libraries\Billing\Installation;
use App\Libraries\Billing\MonthlySubscription;
use App\Libraries\Billing\OtherServices;
use App\Libraries\Billing\Repair;
use App\Models\Billing;
use App\Services\BillingService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    public function store(Request $request, BillingService $service)
    {
        $attributes = $request->input('billing');
        switch ($attributes['billingType']) {
            case '1':
                $billingType = new MonthlySubscription();
                break;
            case '2':
                $billingType = new Installation();
                break;
            case '3':
                $billingType = new Repair();
                break;
            case '4':
                $billingType = new OtherServices();
                break;
        }

        try {
            $service->generateBilling($billingType, $attributes);
            
        } catch (ValidationException $ex) {
            return $this->error($ex->getMessage(), 400);

        } catch (QueryException $ex) {
            return $this->error($ex->getMessage(), 400);

        } catch (\Exception $ex) {
            return $this->error($ex->getMessage(), 400);

        }
        
        return $this->ok('Billing is successfully created for client/s.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $billing = Billing::with([
                'billingItems', 
                'client'
            ])
            ->where('uuid', $uuid)->firstOrFail();

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
