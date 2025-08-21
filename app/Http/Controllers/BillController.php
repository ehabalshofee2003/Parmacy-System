<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Bill_item;
use App\Models\Medicine;
use App\Http\Resources\BillResource;

class BillController extends Controller
{

        public function sendToAdmin($id)
        {
            $bill = Bill::findOrFail($id);

            // تحقق أن الفاتورة لم تُرسل مسبقًا
            if ($bill->status === 'sent') {
                return response()->json([
                    'status' => false,
                    'message' => 'The bill has already been sent.'
                ], 400);
            }

            $bill->status = 'sent';
            $bill->save();

            return response()->json([
                'status' => true,
                'message' => 'The bill has been successfully sent to management.',
                'data' => $bill
            ]);
        }

        public function sendConfirmedBillsToAdmin()
        {
            // الحصول على الفواتير المؤكدة والتي لم تُرسل بعد
            $bills = Bill::where('status', 'confirmed')
                        ->whereNull('sent_at')
                        ->get();

            if ($bills->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'There are no confirmed bill to send.',
                ]);
            }

            foreach ($bills as $bill) {
                // هنا ممكن تضيف منطق الإرسال (بريد - إشعار - إلخ)
                // مثلاً: إرسال إشعار للإدمن، أو حفظ سجل للإرسال

                // تحديث الحالة ووقت الإرسال
                $bill->update([
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'All confirmed Bills have been sent to the Admin.',
                'count' => $bills->count(),
            ]);
        }

        public function getConfirmedBills()
    {
    $userId = auth()->id();

    $bills = Bill::where('user_id', $userId)
        ->where('status', 'pending') // غيّرها حسب حالتك "confirmed"
        ->with('items')
        ->get();

    // تعديل البيانات لتنسيق العناصر
    $bills->transform(function ($bill) {
        $bill->items->transform(function ($item) {
            if ($item->item_type === 'medicine') {
                $product = Medicine::find($item->item_id);
                $item->item_name = $product->name_en ?? 'Unknown Medicine';
                $item->image_url = $product->image_url ?? null;
            } elseif ($item->item_type === 'supply') {
                $product = Supply::find($item->item_id);
                $item->item_name = $product->name_en ?? 'Unknown Supply';
                $item->image_url = $product->image_url ?? null;
            } else {
                $item->item_name = 'Unknown Item';
                $item->image_url = null;
            }

            return $item;
        });
        return $bill;
    });

    return response()->json([
        'status' => true,
        'message' => 'Confirmed Bills have been successfully fetched.',
        'data' => $bills,
    ]);
        }
        public function getConfirmedBillDetails($billId)
{
    $userId = auth()->id();

    $bill = Bill::with('items.medicine')
        ->where('id', $billId)
        ->where('user_id', $userId)
        ->where('status', 'pending')
        ->first();

    if (!$bill) {
        return response()->json([
            'status' => false,
            'message' => 'The bill does not exist or is unconfirmed.',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'The confirmed bill details have been successfully retrieved.',
        'data' => new BillResource($bill),
    ]);
        }


        public function sendSingleBillToAdmin($id)
        {
            try {
                // جلب الفاتورة مع العناصر وعلاقتها بالدواء
                $bill = Bill::with('items.medicine')
                            ->where('id', $id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'pending')
                            ->first();

                if (!$bill) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'The bill does not exist, is not yours, or is already sent.',
                    ]);
                }

                // تحديث حالة الفاتورة
                $bill->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                // إعادة تحميل العلاقة بعد التحديث
                $bill->load('items.medicine');

                // تجهيز البيانات للإرجاع
                $data = [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'status' => $bill->status,
                    'total_amount' => number_format($bill->total_amount, 2),
                    'created_at' => $bill->created_at->format('Y-m-d H:i:s'),
                    'items' => $bill->items->map(function ($item) {
                        return [
                            'item_id' => $item->item_id,
                            'image_url' => $item->medicine?->image_url ?? 'https://via.placeholder.com/150',
                            'stock_quantity' => $item->stock_quantity,
                            'unit_price' => number_format($item->unit_price, 2),
                            'total_price' => number_format($item->total_price, 2),
                        ];
                    }),
                ];

                return response()->json([
                    'status' => 200,
                    'message' => 'The bill has been successfully sent to the Admin.',
                    'data' => $data,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to send bill.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function sendAllBillsToAdmin()
        {
            try {
                $bills = Bill::where('user_id', auth()->id())
                            ->where('status', 'pending')
                            ->get();

                if ($bills->isEmpty()) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'There are no bills to send.',
                    ]);
                }

                foreach ($bills as $bill) {
                    $bill->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }

                return response()->json([
                    'status' => 200,
                    'message' => ' All Bills have been sent successfully.',
                    'data' => BillResource::collection($bills),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'An error occurred while sending Bills.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function getSentBills()
        {
        $sentBills = Bill::where('status', 'sent')->get();

            return response()->json([
                'status' => true,
                'message' => 'The Bills sent to the Admin have been successfully retrieved.',
                'data' => $sentBills,
            ]);
        }

        public function showSentBillDetails($id)
        {
            $bill = Bill::with(['items.medicine'])
                        ->where('id', $id)
                        ->where('status', 'sent')
                        ->first();

            if (!$bill) {
                return response()->json([
                    'status' => false,
                    'message' => 'The sent bill could not be found.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'The details of the successfully sent bill have been retrieved.',
                'data' => [
                    'bill_id'      => $bill->id,
                    'bill_number'  => $bill->bill_number,
                    'date'         => $bill->created_at->toDateString(),
                    'items'        => $bill->items->map(function ($item) {
                        return [
                            'item_type'      => $item->item_type,
                            'item_id'        => $item->item_id,
                            'item_name'      => $item->medicine?->name_en ?? 'Unknown',
                            'stock_quantity' => $item->stock_quantity ?? 0,
                            'image_url'      => $item->medicine?->image_url ?? null,
                            'unit_price'     => number_format($item->unit_price ?? 0, 2),
                            'total_price'    => number_format(($item->stock_quantity ?? 0) * ($item->unit_price ?? 0), 2),
                        ];
                    }),
                    'total'        => number_format($bill->items->sum(function ($item) {
                        return ($item->stock_quantity ?? 0) * ($item->unit_price ?? 0);
                    }), 2),
                ]
            ]);
        }


}
