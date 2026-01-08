<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExpensesRequest\ReplaceExpensesRequest;
use App\Http\Requests\Api\ExpensesRequest\StoreExpensesRequest;
use App\Http\Requests\Api\ExpensesRequest\UpdateExpensesRequest;
use App\Http\Resources\Api\ExpensesResource;
use App\Models\Expenses;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ExpensesController extends ApiController
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

        $query = Expenses::query()
            ->where('is_active', 1);

        if (!empty($include) && $include == 'all') {
            $expenses = $query->orderBy('expense_date', 'asc')->get();
            return ExpensesResource::collection(
                Expenses::with('expenseItems')->paginate()
            );
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('expense_date', 'like', "%{$search}%")
                    ->orWhere('staff_name', 'like', "%{$search}%")
                    ->orWhere('total', 'like', "%{$search}%");
                });
            }
        }

        $expenses = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return ExpensesResource::collection(
            Expenses::with('expenseItems')->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpensesRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Expenses::class);

            return new ExpensesResource(
                Expenses::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Expense Category.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
             $expenses = Expenses::with([
                'expenseItems.expenseCategory'
            ])->where('uuid', $uuid)->firstOrFail();
            return new ExpensesResource($expenses);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Expense Category.', 401);
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

            $expensecategory = Expenses::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpensesResource($expensecategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Expense Category.', 401);
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

            $expensecategory = Expenses::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpensesResource($expensecategory);

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
            $expensecategory = Expenses::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Expense Category.', 401);
        }
    }
}
