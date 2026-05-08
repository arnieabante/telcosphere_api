<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\BillingItemRequest\StoreBillingItemRequest;
use App\Http\Requests\Api\BillingItemRequest\UpdateBillingItemRequest;
use App\Http\Requests\Api\BillingRequest\ReplaceBillingRequest;
use App\Http\Requests\Api\BillingRequest\StoreBillingRequest;
use App\Http\Requests\Api\BillingRequest\UpdateBillingRequest;
use App\Http\Resources\Api\BillingResource;
use App\Libraries\Billing\Installation;
use App\Libraries\Billing\MonthlySubscription;
use App\Libraries\Billing\OtherServices;
use App\Libraries\Billing\Repair;
use App\Libraries\Billing\BillingAdjustment;
use App\Models\Billing;
use App\Services\BillingService;
use App\Services\DashboardService;
use App\Traits\ApiResponses;
use Exception;
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
    public function index(Request $request, DashboardService $service)
    {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');
            $from = $request->get('from');
            $to = $request->get('to');

            $query = Billing::query()
                ->with('client')
                ->with('billingItems')
                ->where('is_active', '=', '1');

            // Filter by date range
            if (!empty($from) && !empty($to)) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('billing_date', 'like', "%{$search}%")
                        ->orWhere('billing_remarks', 'like', "%{$search}%")
                        ->orWhere('billing_total', 'like', "%{$search}%")
                        ->orWhere('billing_description', 'like', "%{$search}%")
                        ->orWhere('billing_status', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                });
            }

        $totalBilling = $service->getTotalPendingBilling();
        $totalGrowth = $service->getBillingGrowth();
        $totalBillingAmount = $service->getTotalBillingAmount();
        $totalAmountGrowth = $service->getMonthlyBillingAmountGrowth();
        $monthlyWifiCollection = $service->getMonthlytWifiCollection();
        $monthlyWifiCollectionGrowth = $service->getMonthlyWifiCollectionGrowth();
        $overdue = $service->getOverdueBillings();

        $billing = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return BillingResource::collection($billing)
        ->additional([
                'meta' => [
                    'billings_total' => $totalBilling,
                    'billings_growth' => $totalGrowth,
                    'billings_amount' => $totalBillingAmount,
                    'billings_amount_growth' => $totalAmountGrowth,
                    'monthly_wifi_collection' => $monthlyWifiCollection,
                    'monthly_wifi_growth' => $monthlyWifiCollectionGrowth,
                    'overdue_accounts' => $overdue
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, BillingService $service)
    {
        try {
            // validate billing
            $billingRequest = app(StoreBillingRequest::class);

            if ($billingRequest->validateResolved()) {
                // validate billing items
                $billingItemsRequest = app(StoreBillingItemRequest::class);
                $billingItemsRequest->validateResolved();
            }

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
                case '5':
                    $billingType = new BillingAdjustment();
                    break;
            }

            $service->generateBilling($billingType, $attributes);

        } catch (ValidationException $ex) {
            return $this->error($ex->getMessage(), 400);

        } catch (QueryException $ex) {
            return $this->error($ex->getMessage(), 400);

        } catch (Exception $ex) {
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
    public function update(Request $request, BillingService $service, string $uuid)
    {
        try {
            // validate billing
            $billingRequest = app(UpdateBillingRequest::class);
            $billingRequest->validateResolved();

            $attributes = $request->input('billing');
            if ($attributes['isActive'] == 0) {
                // de-activate billing
                $billing = $service->deactivateBilling($uuid, $attributes);
            } else {
                // validate billing items
                $billingItemsRequest = app(UpdateBillingItemRequest::class);
                $billingItemsRequest->validateResolved();
                $billing = $service->updateBilling($uuid, $attributes);
            }

            return $billing;

        } catch (ValidationException $ex) {
            return $this->error($ex->getMessage(), 400);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Billing.', 401);

        } catch (Exception $ex) {
            return $this->error($ex->getMessage(), 400);
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

    public function find(Request $request)
    {
        try {
            $billing = Billing::with(['client', 'billingItems'])
                ->where('is_active', 1);

            // STATUS FILTER
            $status = $request->input('status', $request->input('status[]'));

            if (!empty($status)) {
                $billing->whereIn('billing_status', (array) $status);
            }

            // CATEGORY & SERVER
            if ($request->filled('category') || $request->filled('server')) {
                $billing->whereHas('client', function ($query) use ($request) {
                    if ($request->filled('category')) {
                        $query->where('billing_category_id', $request->input('category'));
                    }
                    if ($request->filled('server')) {
                        $query->where('server_id', $request->input('server'));
                    }
                });
            }

            // SEARCH
            if ($request->filled('search')) {
                $search = $request->input('search');

                $billing->where(function ($q) use ($search) {
                    $q->where('billing_date', 'like', "%{$search}%")
                    ->orWhere('billing_remarks', 'like', "%{$search}%")
                    ->orWhere('billing_total', 'like', "%{$search}%")
                    ->orWhere('billing_description', 'like', "%{$search}%")
                    ->orWhere('billing_status', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                });
            }

            // DATE RANGE
            if ($request->filled('from') && $request->filled('to')) {
                $billing->whereBetween('created_at', [$request->from, $request->to]);
            }

            $perPage = $request->input('per_page', 10);

            $rslt = $billing->orderBy('billing_status', 'asc')->paginate($perPage);

            return BillingResource::collection($rslt);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing record not found.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Billing.', 401);
        }
    }
}
