<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExpensesRequest\ReplaceExpensesRequest;
use App\Http\Requests\Api\ExpensesRequest\StoreExpensesRequest;
use App\Http\Requests\Api\ExpensesRequest\UpdateExpensesRequest;
use App\Http\Resources\Api\ExpenseResource;
use App\Models\Expense;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ExpenseController extends ApiController
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');

        $query = Expense::query()
            ->where('is_active', '=', 1)
            ->with(['expenseItems.expenseCategory']);

       if (!empty($search)) {
            $query->where(function ($q) use ($search) {

                $q->where('expense_date', 'like', "%{$search}%")
                ->orWhere('staff_name', 'like', "%{$search}%")
                ->orWhere('total', 'like', "%{$search}%")

                ->orWhereHas('expenseItems', function ($itemQ) use ($search) {
                    $itemQ->where('remark', 'like', "%{$search}%")
                            ->orWhere('amount', 'like', "%{$search}%");
                })

                ->orWhereHas('expenseItems.expenseCategory', function ($catQ) use ($search) {
                    $catQ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });
        }

        return ExpenseResource::collection(
            $query->orderBy('expense_date', 'desc')->paginate($perPage)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpensesRequest $request)
    {
        try {
            return new ExpenseResource(
                Expense::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Expense.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
             $expenses = Expense::with([
                'expenseItems.expenseCategory'
            ])->where('uuid', $uuid)->firstOrFail();
            return new ExpenseResource($expenses);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Expense.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpensesRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Expenses::class);

            $expensecategory = Expense::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpenseResource($expensecategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Expense.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceExpensesRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Expenses::class);

            $expensecategory = Expense::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpenseResource($expensecategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Expense.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $expensecategory = Expense::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Expense.', 401);
        }
    }
}
