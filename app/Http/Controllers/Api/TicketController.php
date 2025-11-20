<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketRequest\ReplaceTicketRequest;
use App\Http\Requests\Api\TicketRequest\StoreTicketRequest;
use App\Http\Requests\Api\TicketRequest\UpdateTicketRequest;
use App\Http\Resources\Api\TicketResource;
use App\Models\Ticket;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TicketController extends ApiController
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

        $query = Ticket::with(['client', 'ticketCategory', 'assignedTo'])
            ->where('is_active', 1);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%") 
                ->orWhereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('ticketCategory', function ($ticketCategoryQuery) use ($search) {
                    $ticketCategoryQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('assignedTo', function ($userQuery) use ($search) {
                    $userQuery->where('fullname', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($statusFilter) && $statusFilter != 'due') {
            $statusMap = [
                'new'     => ['new'],
                'ongoing' => ['assigned', 'ongoing'],
                'done'    => ['done'],
                'hold'    => ['hold'],
            ];

            if (isset($statusMap[$statusFilter])) {
                $query->whereIn('status', $statusMap[$statusFilter]);
            }
        }

        if (!empty($statusFilter) && $statusFilter == 'due') {
            $query->whereDate('due_date', '<=', Carbon::today());
        }

        $tickets = $query->orderBy('created_at', 'desc')
                 ->paginate($perPage)
                 ->appends($request->only(['status', 'search', 'per_page']));

        return TicketResource::collection($tickets)
        ->additional([
            'meta' => [
                'status' => [
                    'total'   => Ticket::where('is_active', 1)->count(),
                    'new' => Ticket::where('status', 'new')->where('is_active', 1)->count(),
                    'ongoing' => Ticket::whereIn('status', ['assigned', 'ongoing'])->where('is_active', 1)->count(),
                    'done' => Ticket::where('status', 'done')->where('is_active', 1)->count(),
                    'hold' => Ticket::where('status', 'hold')->where('is_active', 1)->count(),
                    'due' => Ticket::where('is_active', 1)
                            ->whereDate('due_date', '<=', Carbon::today())
                            ->count(),
                ]
            ]
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Ticket::class);

            return new TicketResource(
                Ticket::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Ticket.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $ticket = Ticket::with(['client', 'ticketCategory', 'assignedTo'])->where('uuid', $uuid)->firstOrFail();
            return new TicketResource($ticket);
            

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Ticket.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Ticket::class);

            $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
            $affected = $ticket->update($request->mappedAttributes());

            return new TicketResource($ticket);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Ticket.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceTicketRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Ticket::class);

            $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
            $affected = $ticket->update($request->mappedAttributes());

            return new TicketResource($ticket);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Ticket.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $ticket = Ticket::where('uuid', $uuid)->firstOrFail();
            $affected = $ticket->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Ticket does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Ticket.', 401);
        }
    }
}
