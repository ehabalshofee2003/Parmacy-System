<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب الإشعارات بنجاح.',
            'data' => $notifications
        ]);
    }
    // 2. إنشاء إشعار جديد
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'تم إرسال الإشعار بنجاح.',
            'data' => $notification
        ]);
    }

   

    // 4. حذف إشعار
    public function destroy($id)
    {
        $notification = Notification::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $notification->delete();

        return response()->json([
            'status' => 200,
            'message' => 'تم حذف الإشعار بنجاح.'
        ]);
    }
 public function getNotifications()
    {
        $user = auth()->user();

        return response()->json([
            'status' => true,
            'notifications' => $user->notifications()->latest()->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    // لتحديد إشعار كمقروء
    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => true, 'message' => 'Notification marked as read']);
        }

        return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
    }

}
