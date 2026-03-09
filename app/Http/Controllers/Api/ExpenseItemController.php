<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExpenseItemRequest\ReplaceExpenseItemRequest;
use App\Http\Requests\Api\ExpenseItemRequest\StoreExpenseItemRequest;
use App\Http\Requests\Api\ExpenseItemRequest\UpdateExpenseItemRequest;
use App\Http\Resources\Api\ExpenseItemResource;
use App\Models\ExpenseItem;
use App\Traits\ApiResponses;
use App\Services\ExpenseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ExpenseItemController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, ExpenseService $service)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
        $dateFilter  = $request->get('date_filter');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = ExpenseItem::query()
            ->where('is_active', 1)
            ->with([
                'expense:id,uuid,expense_date,staff_name,total',
                'expenseCategory:id,name,description'
            ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('remark', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")

                ->orWhereHas('expenseCategory', function ($catQ) use ($search) {
                    $catQ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })

                ->orWhereHas('expense', function ($expQ) use ($search) {
                    $expQ->where('expense_date', 'like', "%{$search}%")
                        ->orWhere('staff_name', 'like', "%{$search}%");
                });
            });
        }

        $query = $service->applyStatusFilter($query, $dateFilter);
        $query = $service->getMonthlyReport($query, $from, $to);
        // Paginated items
        $expenses = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $totals = $service->getTotals();

        return ExpenseItemResource::collection($expenses)
            ->additional([
                'meta' => [
                    'totals' => $totals
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseItemRequest $request)
    {
        try {
            return new ExpenseItemResource(
                ExpenseItem::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Expense Item.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
         try {
            $item = ExpenseItem::where('uuid', $uuid)->firstOrFail();
            return new ExpenseItemResource($item);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Expense Item.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseItemRequest $request, string $uuid)
    {
        try {
            $item = ExpenseItem::where('uuid', $uuid)->firstOrFail();
            $item->update($request->mappedAttributes());

            return new ExpenseItemResource($item->fresh());

        } catch (ModelNotFoundException $ex) {
            return response()->json([
                'message' => 'Expense item does not exist.'
            ], 404);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceExpenseItemRequest $request, string $uuid)
    {
        try {
            $expenseitem = ExpenseItem::where('uuid', $uuid)->firstOrFail();
            $affected = $expenseitem->update($request->mappedAttributes());

            return new ExpenseItemResource($expenseitem);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Expense Items.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $expenseitem = ExpenseItem::where('uuid', $uuid)->firstOrFail();
            $affected = $expenseitem->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Expense Items.', 401);
        }
    }
}
