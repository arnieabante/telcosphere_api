<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdvancePaymentsRequest\ReplaceAdvancePaymentsRequest;
use App\Http\Requests\Api\AdvancePaymentsRequest\StoreAdvancePaymentsRequest;
use App\Http\Requests\Api\AdvancePaymentsRequest\UpdateAdvancePaymentsRequest;
use App\Http\Resources\Api\AdvancePaymentsResource;
use App\Models\AdvancePayments;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdvancePaymentsController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $include = $request->get('include');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = AdvancePayments::query()->with('client');

        if (!empty($include) && $include == 'all') {
            $advancepayments = $query->orderBy('created_at', 'desc')->get();
            return AdvancePaymentsResource::collection($advancepayments);

        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('amount', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    });
                });
            }
        }
        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $advancepayments = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return AdvancePaymentsResource::collection($advancepayments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdvancePaymentsRequest $request)
    {
        try {
            // a client has a single advance payment record, new deposits add to it
            return DB::transaction(function () use ($request) {
                $attributes = $request->mappedAttributes();

                $advancepayments = AdvancePayments::where('client_id', $attributes['client_id'])
                    ->lockForUpdate()
                    ->first();

                if ($advancepayments) {
                    $advancepayments->update([
                        'amount' => round($advancepayments->amount + $attributes['amount'], 2)
                    ]);
                } else {
                    $advancepayments = AdvancePayments::create($attributes);
                }

                return new AdvancePaymentsResource($advancepayments);
            });

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create an Advance Payment.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $advancepayments = AdvancePayments::where('uuid', $uuid)->firstOrFail();
            return new AdvancePaymentsResource($advancepayments);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Advance Payment does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view an Advance Payment.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdvancePaymentsRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', AdvancePayments::class);

            $advancepayments = AdvancePayments::where('uuid', $uuid)->firstOrFail();
            $affected = $advancepayments->update($request->mappedAttributes());

            return new AdvancePaymentsResource($advancepayments);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Advance Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update an Advance Payment.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceAdvancePaymentsRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', AdvancePayments::class);

            $advancepayments = AdvancePayments::where('uuid', $uuid)->firstOrFail();
            $affected = $advancepayments->update($request->mappedAttributes());

            return new AdvancePaymentsResource($advancepayments);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Advance Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace an Advance Payment.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $advancepayments = AdvancePayments::where('uuid', $uuid)->firstOrFail();
            $affected = $advancepayments->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Advance Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete an Advance Payment.', 401);
        }
    }
}
