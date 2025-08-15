<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MedicineAlertNotification extends Notification
{
    use Queueable;

    public $medicine;
    public $type;
    public $message;

    public function __construct($medicine, $type, $message)
    {
        $this->medicine = $medicine;
        $this->type = $type;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database']; // يخزن في جدول notifications
    }

    public function toDatabase($notifiable)
    {
        return [
            'medicine_id' => $this->medicine->id,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}
