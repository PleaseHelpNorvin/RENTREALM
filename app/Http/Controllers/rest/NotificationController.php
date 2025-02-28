<?php

namespace App\Http\Controllers\rest;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    //

    public function index($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)
            ->with('notifiable')
            ->get();

        if ($notifications->isEmpty()) {
            return $this->notFoundResponse(null, "No notifications found for User $user_id.");
        }

        return $this->successResponse(['notifications' => $notifications], "Notifications found for User $user_id.");
    }

    public function indexUnread($user_id)
    {
        $unreadNotifications = Notification::where('user_id', $user_id)
            ->where('is_read', false)
            ->get();

        if ($unreadNotifications->isEmpty()) {
            return $this->notFoundResponse(null, "No unread notifications found for User $user_id.");
        }

        return $this->successResponse(['notifications' => [$unreadNotifications]], "Unread notifications found for User $user_id.");
    }

    public function indexRead($user_id)
    {
        $readNotifications = Notification::where('user_id', $user_id)
            ->where('is_read', true)
            ->get();

        if ($readNotifications->isEmpty()) {
            return $this->notFoundResponse(null, "No read notifications found for User $user_id.");
        }

        return $this->successResponse(['notifications' => [$readNotifications]], "Read notifications found for User $user_id.");
    }


    public function show($id)
    {
        $showNotification = Notification::find($id);

        if (!$showNotification) {
            return $this->notFoundResponse(null, "No notification with id $id found");
        }

        return $this->successResponse(['notification' => [$showNotification]], "Notification $id Found");
    }

    public function updateIsRead($id) {
        $notification = Notification::where('id', $id)
            ->first();
    
        if (!$notification) {
            return $this->notFoundResponse(null, "Notification $id not found.");
        }
    
        $notification->update(['is_read' => 1]);
        // $notification->update(['is_read' => true]);

        return $this->successResponse(['notifications' => [$notification]], "Notification $id marked as read.");
    }

}
