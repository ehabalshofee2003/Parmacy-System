<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Medicine;
use App\Models\User;
use App\Notifications\MedicineAlertNotification;
use Carbon\Carbon;

class CheckMedicineAlerts extends Command
{
  protected $signature = 'medicines:check-alerts';
    protected $description = 'Check medicines for expiry and stock alerts';

    public function handle()
    {
        $today = Carbon::today();

        $medicines = Medicine::all();

        foreach ($medicines as $medicine) {

    // ✅ جلب كل المستخدمين المستلمين: الصيادلة + الأدمن
    $users = User::whereIn('role', ['pharmacist', 'admin'])->get();

    // 1️⃣ قرب انتهاء الصلاحية
    if ($medicine->expiry_date && $today->diffInDays($medicine->expiry_date, false) <= 7 && $today <= $medicine->expiry_date) {
 
        foreach ($users as $user) {
            $user->notify(new MedicineAlertNotification($medicine, 'expiry_soon', "الدواء {$medicine->name} على وشك انتهاء صلاحيته"));
        }
    }

    // 2️⃣ انتهاء الصلاحية
    if ($medicine->expiry_date && $today > $medicine->expiry_date) {

        foreach ($users as $user) {
            $user->notify(new MedicineAlertNotification($medicine, 'expired', "الدواء {$medicine->name} انتهت صلاحيته"));
        }
    }

    // 3️⃣ قرب النفاذ
    if ($medicine->quantity <= $medicine->reorder_level && $medicine->quantity > 0) {
        $message = "الدواء {$medicine->name} قريب من النفاذ، الكمية المتبقية: {$medicine->quantity}";

        foreach ($users as $user) {
            $user->notify(new MedicineAlertNotification($medicine, 'low_stock', $message));
        }
    }

    // 4️⃣ نفاد المخزون
    if ($medicine->quantity == 0) {
        $message = "الدواء {$medicine->name} نفد من المخزون!";

        foreach ($users as $user) {
            $user->notify(new MedicineAlertNotification($medicine, 'out_of_stock', $message));
        }
    }

        $this->info('Medicine alerts checked successfully!');
    }
}
}
