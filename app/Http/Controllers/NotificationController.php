<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get paginated/recent notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $notifications = $user->notifications()->latest()->take(20)->get();
        $unreadCount = $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        $unreadCount = $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Notification marked as read',
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
            ],
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $user->notifications()->where('id', $id)->delete();
        $unreadCount = $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Notification deleted',
                'unread_count' => $unreadCount,
            ],
        ]);
    }
}

