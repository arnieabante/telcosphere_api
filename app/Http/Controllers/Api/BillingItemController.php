<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BillingItemRequest\ReplaceBillingItemRequest;
use App\Http\Requests\Api\BillingItemRequest\StoreBillingItemRequest;
use App\Http\Requests\Api\BillingItemRequest\UpdateBillingItemRequest;
use App\Http\Requests\Api\BillingRequest\StoreBillingRequest;
use App\Http\Resources\Api\BillingItemResource;
use App\Models\BillingItem;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BillingItemController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = BillingItem::query()->where('is_active', '=', '1');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('billing_item_name', 'like', "%{$search}%")
                    ->orWhere('billing_item_quantity', 'like', "%{$search}%")
                    ->orWhere('billing_item_remark', 'like', "%{$search}%")
                    ->orWhere('billing_item_amount', 'like', "%{$search}%")
                    ->orWhere('billing_item_total', 'like', "%{$search}%")
                    ->orWhere('billing_status', 'like', "%{$search}%");
            });
        }

        $item = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return BillingItemResource::collection($item);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillingItemRequest $request)
    {
        try {
            return new BillingItemResource(
                BillingItem::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Billing Item.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $item = BillingItem::where('uuid', $uuid)->firstOrFail();
            return new BillingItemResource($item);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Billing Item.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillingItemRequest $request, string $uuid)
    {
        try {
            $item = BillingItem::where('uuid', $uuid)->firstOrFail();
            $affected = $item->update($request->mappedAttributes());
            
            return new BillingItemResource($item);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Item does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Billing Item.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceBillingItemRequest $request, string $uuid)
    {
        try {
            $item = BillingItem::where('uuid', $uuid)->firstOrFail();
            $affected = $item->update($request->mappedAttributes());
            
            return new BillingItemResource($item);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Billing Item.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $item = BillingItem::where('uuid', $uuid)->firstOrFail();
            $affected = $item->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Billing Item does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Billing Item.', 401);
        }
    }
}
