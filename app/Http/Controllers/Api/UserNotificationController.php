<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserNotificationResource;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    // GET /notifications?status=unread|read|all
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $status = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        $query = $user->notifications()->latest('created_at');

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $paginator = $query->paginate(min(max($perPage, 1), 100));

        return UserNotificationResource::collection($paginator)->response();
    }

    // POST /notifications/{id}/read
    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        $user = $request->user();

        $model = $user->notifications()->whereKey($notification)->firstOrFail();

        $model->markAsRead();

        return (new UserNotificationResource($model))->response();
    }

    // POST /notifications/read-all
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => __('notifications.all_marked_as_read'),
        ]);
    }

    // GET /notifications/unread-count
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $user->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
