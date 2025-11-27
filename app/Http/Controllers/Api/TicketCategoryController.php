<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketCategoryRequest\ReplaceTicketCategoryRequest;
use App\Http\Requests\Api\TicketCategoryRequest\StoreTicketCategoryRequest;
use App\Http\Requests\Api\TicketCategoryRequest\UpdateTicketCategoryRequest;
use App\Http\Resources\Api\TicketCategoryResource;
use App\Models\TicketCategory;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TicketCategoryController extends ApiController
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

        $query = TicketCategory::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $ticketcategories = $query->orderBy('name', 'asc')->get();
            return TicketCategoryResource::collection($ticketcategories);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        $ticketcategories = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return TicketCategoryResource::collection($ticketcategories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketCategoryRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', TicketCategory::class);

            return new TicketCategoryResource(
                TicketCategory::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Ticket Category.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $ticketcategory = TicketCategory::where('uuid', $uuid)->firstOrFail();
            return new TicketCategoryResource($ticketcategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket Category does not exist.', 404);
        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Ticket Category.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketCategoryRequest $request, string $uuid)
    {
        try { 
            // update policy
            // $this->isAble('update', TicketCategory::class);

            $ticketcategory = TicketCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketcategory->update($request->mappedAttributes());
            
            return new TicketCategoryResource($ticketcategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Ticket Category.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceTicketCategoryRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', TicketCategory::class);
            
            $ticketcategory = TicketCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketcategory->update($request->mappedAttributes());
            
            return new TicketCategoryResource($ticketcategory);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Ticket Category.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $ticketcategory = TicketCategory::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketcategory->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket Category does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Ticket Category.', 401);
        }
    }
}
