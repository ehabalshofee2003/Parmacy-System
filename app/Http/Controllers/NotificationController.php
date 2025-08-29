<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
public function sendNotificationToUser(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'title' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    $user = User::findOrFail($request->user_id);

    // إذا المستخدم ما عنده fcm_token مسجل
    if (!$user->fcm_token) {
        return response()->json([
            'status' => 400,
            'message' => 'User does not have a registered FCM token'
        ], 400);
    }

    $this->notificationService->send($user, $request->title, $request->message);

    return response()->json([
        'status' => 200,
        'message' => 'Notification sent successfully'
    ], 200);
}
public function sendNotificationToAll(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    $users = User::whereNotNull('fcm_token')->get();

    foreach ($users as $user) {
        $this->notificationService->send($user, $request->title, $request->message);
    }

    return response()->json([
        'status' => 200,
        'message' => 'Notification sent to all users'
    ], 200);
}

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function testNotificationToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $user = User::findOrFail(1);
        $this->notificationService->send($user, $request->title, $request->message);
        return response()->json([
            'status' => 200,
            'message' => 'Notification sent'
        ], 200);
    }

 public function testNotification()
    {
        $user = User::find(1);
        $user->fcm_token = "dnfWfRDXWlI14MNWGB8Qr8:APA91bFfNBnmDTd-8PFWH-3eK90GRPDlZaFhSPIPK6wzTUhJOU3MPilZH9PxvbqfKvwup_PVi4IVwrMSUGT-_cpLB9Hu0Bw1xgWEIFoX7u_jBKJL8QgM_78";
        $user->save();
        $this->notificationService->send($user, "Test", "Testing notifications");
        return response()->json([
            'status' => 200,
            'message' => 'Notification sent'
        ], 200);
    }

}




