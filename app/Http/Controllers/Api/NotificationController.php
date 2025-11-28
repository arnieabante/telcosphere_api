<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NotificationRequest\ReplaceNotificationRequest;
use App\Http\Requests\Api\NotificationRequest\StoreNotificationRequest;
use App\Http\Requests\Api\NotificationRequest\UpdateNotificationRequest;
use App\Http\Resources\Api\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class NotificationController extends ApiController
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

        $query = Notification::query()
            ->where('is_active', 1);
        if (!empty($include) && $include == 'all') {
            $notifications = $query->orderBy('name', 'asc')->get();
            return NotificationResource::collection($notifications);
        } else {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return NotificationResource::collection($notifications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotificationRequest $request)
    {
        try {
            // create policy
            // $this->isAble('create', Notification::class);

            return new NotificationResource(
                Notification::create($request->mappedAttributes())
            );

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to create a Notification.', 401);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $notification = Notification::where('uuid', $uuid)->firstOrFail();
            return new NotificationResource($notification);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Notification does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to view a Notification.', 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationRequest $request, string $uuid)
    {
        try {
            // update policy
            // $this->isAble('update', Notification::class);

            $notification = Notification::where('uuid', $uuid)->firstOrFail();
            $affected = $notification->update($request->mappedAttributes());

            return new NotificationResource($notification);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Notification does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to update a Notification.', 401);
        }
    }

    /**
     * Replace the specified resource in storage.
     */
    public function replace(ReplaceNotificationRequest $request, string $uuid)
    {
        try {
            // replace policy
            // $this->isAble('replace', Notification::class);

            $notification = Notification::where('uuid', $uuid)->firstOrFail();
            $affected = $notification->update($request->mappedAttributes());

            return new NotificationResource($notification);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Notification does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to replace a Notification.', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $notification = Notification::where('uuid', $uuid)->firstOrFail();
            $affected = $notification->delete();

            return $this->ok("Deleted $affected record.", []);

        } catch (ModelNotFoundException $ex) {
            return $this->error('Notification does not exist.', 404);

        } catch (AuthorizationException $ex) {
            return $this->error('You are not authorized to delete a Notification.', 401);
        }
    }
}
