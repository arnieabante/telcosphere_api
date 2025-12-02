<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TicketLogRequest\ReplaceTicketLogRequest;
use App\Http\Requests\Api\TicketLogRequest\StoreTicketLogRequest;
use App\Http\Requests\Api\TicketLogRequest\UpdateTicketLogRequest;
use App\Http\Resources\Api\TicketLogResource;
use App\Models\TicketLog;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TicketLogController extends ApiController
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

        $query = TicketLog::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $ticketLogs = $query->orderBy('name', 'asc')->get();
            return TicketLogResource::collection($ticketLogs);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        $ticketLogs = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return TicketLogResource::collection($ticketLogs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketLogRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', TicketLog::class);

            return new TicketLogResource(
                TicketLog::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a TicketLog.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $ticketLog = TicketLog::where('uuid', $uuid)->firstOrFail();
            return new TicketLogResource($ticketLog);

        } catch (ModelNotFoundException $ex) {
            return $this->error('TicketLog does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a TicketLog.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketLogRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', TicketLog::class);

            $ticketLog = TicketLog::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketLog->update($request->mappedAttributes());

            return new TicketLogResource($ticketLog);

        } catch (ModelNotFoundException $ex) {
            return $this->error('TicketLog does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a TicketLog.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceTicketLogRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', TicketLog::class);

            $ticketLog = TicketLog::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketLog->update($request->mappedAttributes());

            return new TicketLogResource($ticketLog);

        } catch (ModelNotFoundException $ex) {
            return $this->error('TicketLog does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a TicketLog.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $ticketLog = TicketLog::where('uuid', $uuid)->firstOrFail();
            $affected = $ticketLog->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('TicketLog does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a TicketLog.', 401);
        }
    }
}
