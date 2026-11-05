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

        $query = AdvancePayments::query()
            ->where('is_active', 1);

        if (!empty($include) && $include == 'all') {
            $expensecategories = $query->orderBy('name', 'asc')->get();
            return AdvancePaymentsResource::collection($expensecategories);

        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }
        // Filter by date range
        if (!empty($from) && !empty($to)) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $expensecategories = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return AdvancePaymentsResource::collection($expensecategories);
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(StoreAdvancePaymentsRequest $request)
    {
        try {
            $attributes = $request->mappedAttributes();

            $advancePayment = AdvancePayments::where('client_id', $attributes['client_id'])
                ->first();

            if ($advancePayment) {
                $advancePayment->amount += $attributes['amount'];
                $advancePayment->save();
            } else {
                $advancePayment = AdvancePayments::create($attributes);
            }

            return new AdvancePaymentsResource($advancePayment);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create an Expense Category.', 401);
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
            return $this->error('Expense Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Expense Category.', 401);
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
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Expense Category.', 401);
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
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Expense Category.', 401);
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
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Expense Category.', 401);
        }
    }
}
