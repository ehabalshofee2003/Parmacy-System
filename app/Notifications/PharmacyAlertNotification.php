<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PharmacyAlertNotification extends Notification
{
    use Queueable;

     protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => $this->data['type'],         // مثل: expired_medicine
            'title' => $this->data['title'],       // عنوان الإشعار
            'message' => $this->data['message'],   // نص الإشعار
            'color' => $this->data['color'],       // اللون لعرضه في الواجهة
            'medicine_id' => $this->data['medicine_id'] ?? null,
            'extra_data' => $this->data['extra'] ?? null,
        ];
    }
}
