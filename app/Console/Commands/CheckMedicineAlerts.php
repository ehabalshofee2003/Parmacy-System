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

        // جلب كل المستخدمين المستلمين: الصيادلة + الأدمن
        $users = User::whereIn('role', ['pharmacist', 'admin'])->get();

        if ($users->isEmpty()) {
            $this->info('No users to notify.');
            return;
        }

        foreach ($medicines as $medicine) {

            // 1️⃣ قرب انتهاء الصلاحية (7 أيام)
            if ($medicine->expiry_date && $today->diffInDays($medicine->expiry_date, false) <= 7 && $today <= $medicine->expiry_date) {
                foreach ($users as $user) {
                    $user->notify(new MedicineAlertNotification(
                        $medicine,
                        'expiry_soon',
                        "الدواء {$medicine->name_en} على وشك انتهاء صلاحيته"
                    ));
                }
            }

            // 2️⃣ انتهاء الصلاحية
            if ($medicine->expiry_date && $today > $medicine->expiry_date) {
                foreach ($users as $user) {
                    $user->notify(new MedicineAlertNotification(
                        $medicine,
                        'expired',
                        "الدواء {$medicine->name_en} انتهت صلاحيته"
                    ));
                }
            }

            // 3️⃣ قرب النفاذ
            if ($medicine->stock_quantity <= $medicine->reorder_level && $medicine->stock_quantity > 0) {
                foreach ($users as $user) {
                    $user->notify(new MedicineAlertNotification(
                        $medicine,
                        'low_stock',
                        "الدواء {$medicine->name_en} قريب من النفاذ، الكمية المتبقية: {$medicine->stock_quantity}"
                    ));
                }
            }

            // 4️⃣ نفاد المخزون
            if ($medicine->stock_quantity == 0) {
                foreach ($users as $user) {
                    $user->notify(new MedicineAlertNotification(
                        $medicine,
                        'out_of_stock',
                        "الدواء {$medicine->name_en} نفد من المخزون!"
                    ));
                }
            }
        }

        $this->info('Medicine alerts checked successfully!');
    }
}
