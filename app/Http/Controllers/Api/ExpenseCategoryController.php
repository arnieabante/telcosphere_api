<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExpenseCategoryRequest\ReplaceExpenseCategoryRequest;
use App\Http\Requests\Api\ExpenseCategoryRequest\StoreExpenseCategoryRequest;
use App\Http\Requests\Api\ExpenseCategoryRequest\UpdateExpenseCategoryRequest;
use App\Http\Resources\Api\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ExpenseCategoryController extends ApiController
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

        $query = ExpenseCategory::query()
            ->where('is_active', 1);

        if (!empty($include) && $include == 'all') {
            $expensecategories = $query->orderBy('name', 'asc')->get();
            return ExpenseCategoryResource::collection($expensecategories);

        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        $expensecategories = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return ExpenseCategoryResource::collection($expensecategories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseCategoryRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', ExpenseCategory::class);

            return new ExpenseCategoryResource(
                ExpenseCategory::create($request->mappedAttributes())
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
            $expensecategory = ExpenseCategory::where('uuid', $uuid)->firstOrFail();
            return new ExpenseCategoryResource($expensecategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Expense Category.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseCategoryRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', ExpenseCategory::class);

            $expensecategory = ExpenseCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpenseCategoryResource($expensecategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Expense Category.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceExpenseCategoryRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', ExpenseCategory::class);

            $expensecategory = ExpenseCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->update($request->mappedAttributes());

            return new ExpenseCategoryResource($expensecategory);

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
            $expensecategory = ExpenseCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $expensecategory->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Expense Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Expense Category.', 401);
        }
    }
}
