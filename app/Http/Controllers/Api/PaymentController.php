<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentRequest\ReplacePaymentRequest;
use App\Http\Requests\Api\PaymentRequest\StorePaymentRequest;
use App\Http\Requests\Api\PaymentRequest\UpdatePaymentRequest;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $statusFilter = $request->get('status');
        $clientUuid = $request->get('client_id');

        $query = Payment::with(['client', 'collectedBy'])
            ->where('is_active', 1);

        if(!empty($clientUuid) || $clientUuid != ''){
            $client = \App\Models\Client::where('uuid', $clientUuid)->first();
            $query->where('client_id', $client->id);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                ->orWhere('payment_date', 'like', "%{$search}%") 
                ->orWhere('payment_amount', 'like', "%{$search}%") 
                ->orWhere('reference', 'like', "%{$search}%") 
                ->orWhereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('collectedBy', function ($userQuery) use ($search) {
                    $userQuery->where('fullname', 'like', "%{$search}%");
                });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
                 ->paginate($perPage)
                 ->appends($request->only(['status', 'search', 'per_page']));

        return PaymentResource::collection($payments);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Payment::class);

            return new PaymentResource(
                Payment::create($request->mappedAttributes())
            );

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
            // update policy
            // $this->isAble('update', Payment::class);

            $payment = Payment::where('uuid', $uuid)->firstOrFail();
            $affected = $payment->update($request->mappedAttributes());

            return new PaymentResource($payment);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Payment does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Payment.', 401);
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
