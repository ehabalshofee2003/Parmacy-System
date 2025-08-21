<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MedicineAlertNotification extends Notification
{
     use Queueable;

    protected $medicine;
    protected $type;
    protected $message;

    public function __construct($medicine, $type, $message)
    {
        $this->medicine = $medicine;
        $this->type = $type;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database']; // الإشعار فقط على قاعدة البيانات
    }

    public function toDatabase($notifiable)
    {
        return [
            'medicine_id' => $this->medicine->id,
            'type'        => $this->type,
            'message'     => $this->message,
        ];
    }
}
