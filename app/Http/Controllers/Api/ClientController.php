<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientRequest\ReplaceClientRequest;
use App\Http\Requests\Api\ClientRequest\StoreClientRequest;
use App\Http\Requests\Api\ClientRequest\UpdateClientRequest;
use App\Http\Resources\Api\ClientResource;
use App\Models\Client;
use App\Services\DashboardService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, DashboardService $service)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $include = $request->get('include');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Client::with(['internetPlan', 'billingCategory', 'server', 'billings'])
            ->where('is_active', 1);

        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        if (!empty($include) && $include == 'all') {
           $clients = $query->orderBy('first_name', 'asc')->get();
           return ClientResource::collection($clients);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('installation_date', 'like', "%{$search}%")
                    ->orWhere('house_no', 'like', "%{$search}%")
                    ->orWhereHas('internetPlan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('server', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('billingCategory', function ($billingQuery) use ($search) {
                        $billingQuery->where('name', 'like', "%{$search}%");
                    });
                });
            }
        }
        $totalClient = $service->getTotalClient();
        $totalGrowth = $service->getClientGrowth();

        $clients = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ClientResource::collection($clients)
            ->additional([
                'meta' => [
                    'clients_total' => $totalClient,
                    'clients_growth' => $totalGrowth
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Client::class);

            return new ClientResource(
                Client::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Client.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $client = Client::with(['internetPlan', 'billingCategory', 'server', 'billings'])->where('uuid', $uuid)->firstOrFail();
            return new ClientResource($client);


        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Client.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Client::class);

            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->update($request->mappedAttributes());

            return new ClientResource($client);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Client.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceClientRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Client::class);

            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->update($request->mappedAttributes());

            return new ClientResource($client);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Client.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();
            $affected = $client->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Client.', 401);
        }
    }

    /**
     * Get billing for client
     */

    public function billings(Request $request, string $uuid)
    {
        $filter = $request->get('filter'); // e.g., 'status'
        $value  = $request->get('value');  // e.g., 'Pending'

        try {
            $client = Client::with(['billings.billingItems' => function($query) use ($filter, $value) {
                if ($filter && $value) {
                     if ($filter === 'billing_status' && $value === 'Pending') {
                        $query->whereIn($filter, ['Pending', 'Partial']);
                    } else {
                        $query->where($filter, $value);
                    }
                }
            }])
            ->where('uuid', $uuid)
            ->where('is_active', 1)
            ->firstOrFail();

            return new ClientResource($client);

        } catch (ModelNotFoundException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Client does not exist.'
            ], 404);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client billings.',
                'error'=>$ex->getMessage()
            ], 500);
        }
    }

    public function fetchClientSOA(Request $request, string $uuid)
    {
        try {
            $client = Client::with('internetPlan')
                ->where('uuid', $uuid)
                ->where('is_active', 1)
                ->firstOrFail();

            // Fetch client Transaction History
            $soaData = $client->getSOA($request->only(['from', 'to']));
            $previousBalance = $soaData['previous_balance'];
            $soa = collect($soaData['transactions']);

            $balance = $previousBalance;

            $soa->prepend((object)[
                'soa_date' => $request->input('from'),
                'particulars' => 'Previous Balance',
                'debit' => $previousBalance > 0 ? $previousBalance : 0,
                'credit' => $previousBalance < 0 ? abs($previousBalance) : 0,
                'created_at' => now(),
            ]);

            // Running Balance
            $soa = $soa->map(function ($row) use (&$balance) {
                $balance += ($row->debit - $row->credit);
                // Format running balance with 2 decimals
                $row->balance = number_format($balance, 2, '.', ',');
                return $row;
            });

            $latestBilling = DB::table('billings')
                ->where('client_id', $client->id)
                ->latest('billing_date')
                ->first();

            $previousBalanceDisplay = $latestBilling->balance_from_prev_billing ?? 0;
            $billingTotal = $latestBilling->billing_total ?? 0;
            $monthlyFee = $client->internetPlan->monthly_subscription ?? 0;
            $amountDue = $latestBilling->billing_balance ?? 0;
            $status = $latestBilling->billing_status ?? 'No Billing';

            $totalDebit  = $soa->sum('debit');
            $totalCredit = $soa->sum('credit');
            $finalBalance = $totalDebit - $totalCredit;

            return response()->json([
                'success' => true,
                'total' => [
                    'total_debit'  => number_format($totalDebit, 2, '.', ','),
                    'total_credit' => number_format($totalCredit, 2, '.', ','),
                    'balance'      => number_format($finalBalance, 2, '.', ','),
                ],
                'data' => $soa,
                'client' => $client
            ]);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);
        }
    }

    public function fetchAccountHistory(Request $request, string $uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)
                ->where('is_active', 1)
                ->firstOrFail();

            $soa = $client->getAccountHistory($request->only(['from', 'to']));

            // Running balance
            $balance = 0;
            $soa = $soa->map(function ($row) use (&$balance) {
                $balance += ($row->debit - $row->credit);
                // Format running balance with 2 decimals
                $row->balance = number_format($balance, 2, '.', ',');
                return $row;
            });

            $totalDebit  = $soa->sum('debit');
            $totalCredit = $soa->sum('credit');
            $finalBalance = $totalDebit - $totalCredit;

            return response()->json([
                'success' => true,
                'total' => [
                    'total_debit'  => number_format($totalDebit, 2, '.', ','),
                    'total_credit' => number_format($totalCredit, 2, '.', ','),
                    'balance'      => number_format($finalBalance, 2, '.', ','),
                ],
                'data' => $soa
            ]);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Client does not exist.', 404);
        }
    }
}
