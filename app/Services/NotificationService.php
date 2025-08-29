<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{
    private function messaging()
    {
        $serviceAccountPath = base_path(env('FIREBASE_CREDENTIALS', 'storage\app\firebase\pms-project-caf37-25e0c23bb638.json'));
        $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($serviceAccountPath);
        return $factory->createMessaging();
    }

    public function index()
    {
        return auth()->user()->notifications;
    }

    public function getUnreadNotifications()
    {
        // Get unread notifications for the authenticated user
        return auth()->user()->notifications()->unread()->get();
    }

    public function send($user, $title, $message, $type = 'basic')
    {
        if (empty($user['fcm_token'])) {
            Log::warning('User has no fcm_token', ['user_id' => $user['id'] ?? null]);
            return 0;
        }

        $messaging = $this->messaging();

        // Prepare the notification array
        $notification = [
            'title' => $title,
            'body' => $message,
            'sound' => 'default',
        ];

        // Additional data payload
        $data = [
            'type' => $type,
            'id' => $user['id'],
            'message' => $message,
        ];

        // Create the CloudMessage instance
        $cloudMessage = CloudMessage::withTarget('token', $user['fcm_token'])
            ->withNotification($notification)
            ->withData($data);

        try {
            $messaging->send($cloudMessage);

            NotificationModel::query()->create([
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'user' => $user->first_name . ' ' . $user->last_name,
                    'message' => $message,
                    'title' => $title,
                ]),
            ]);
            Log::debug('FCM token snapshot', [
                'user_id' => $user->id ?? null,
                'len'     => isset($user->fcm_token) ? strlen($user->fcm_token) : 0,
                'start'   => substr((string)$user->fcm_token, 0, 16),
                'end'     => substr((string)$user->fcm_token, -16),
            ]);
        } catch (MessagingException $e) {
            Log::error($e->getMessage());
            return 0;
        } catch (FirebaseException $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }

    public function markAsRead($notificationId): bool
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);

        if (isset($notification)) {
            $notification->markAsRead();
            return true;
        } else return false;
    }

    public function destroy($id): bool
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if (isset($notification)) {
            $notification->delete();
            return true;
        } else return false;
    }

    public function sendNotificationToTopic($title, $body, $data1, $topic)
    {
        $messaging = $this->messaging();

        $cloudMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('topic', $topic)
            ->withNotification(['title' => $title, 'body' => $body, 'sound' => 'default'])
            ->withData(['data' => $data1]);

        try {
            $messaging->send($cloudMessage);

            NotificationModel::query()->create([
                'type' => 'topic',
                'notifiable_type' => User::class, // or a Topic model if you really have one
                'notifiable_id' => null,
                'data' => [
                    'topic' => $topic,
                    'message' => $body,
                    'title' => $title,
                ],
            ]);
            return 1;
        } catch (MessagingException|FirebaseException $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }


    public function sendNotification($token, $title, $body, $data = [])
    {
        if (empty($token)) return;

        $messaging = $this->messaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => $title, 'body' => $body])
            ->withData($data);

        $messaging->send($message);
    }



    public function notifyAllUsers($title, $message, $type = 'basic')
{
    $users = User::whereNotNull('fcm_token')->get();

    foreach ($users as $user) {
        $this->send($user, $title, $message, $type);
    }

    return true;
}


}
