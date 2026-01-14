<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends BaseApiController
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
        $this->middleware('permission:notifications.view')->only(['index', 'unreadCount']);
        $this->middleware('permission:notifications.create')->only('send');
        $this->middleware('permission:notifications.update')->only(['markAsRead', 'markAllAsRead']);
        $this->middleware('permission:notifications.delete')->only('destroy');
    }

    /**
     * NOT-008: Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = QueryBuilder::for(Notification::query())
            ->where('user_id', $request->user()->id)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('sms_status'),
                AllowedFilter::scope('is_read'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->get('per_page', 15))
            ->appends($request->query());

        return $this->success(
            NotificationResource::collection($notifications),
            'Notifications récupérées avec succès'
        );
    }

    /**
     * NOT-007: Send notification (admin only)
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $notifications = $this->notificationService->send(
            userIds: $request->recipient_ids,
            title: $request->title,
            message: $request->message,
            type: $request->type,
            channels: $request->channels ?? ['in_app'],
            metadata: $request->metadata ?? []
        );

        return $this->success([
            'count' => $notifications->count(),
        ], 'Notifications envoyées avec succès', 201);
    }

    /**
     * NOT-009: Mark notification as read
     */
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->markAsRead();

        return $this->success([
            'notification' => new NotificationResource($notification->fresh()),
        ], 'Notification marquée comme lue');
    }

    /**
     * NOT-010: Mark all as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return $this->success([
            'count' => $count,
        ], 'Toutes les notifications ont été marquées comme lues');
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return $this->success([
            'count' => $count,
        ], 'Unread notifications count');
    }

    /**
     * Delete notification
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->delete();

        return $this->success(null, 'Notification supprimée');
    }
}
