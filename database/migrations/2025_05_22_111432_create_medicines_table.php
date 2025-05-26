<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name_en');                    // الاسم الأجنبي
            $table->string('name_ar');                    // الاسم العربي
            $table->string('barcode')->unique();          // رقم الباركود
            $table->unsignedBigInteger('category_id'); // مفتاح أجنبي لصنف الدواء
            $table->string('manufacturer');               // اسم الشركة المصنعة
            $table->string('country_of_origin');          // بلد المنشأ
            $table->decimal('pharmacy_price', 8, 2);      // سعر الشراء من المستودع
            $table->decimal('consumer_price', 8, 2);      // سعر البيع للمستهلك
            $table->decimal('discount', 5, 2)->nullable(); // نسبة الخصم (اختياري)
            $table->integer('stock_quantity');            // الكمية بالمخزن
            $table->date('expiry_date');                  // تاريخ انتهاء الصلاحية
            $table->string('form');                       // الشكل (حب، شراب...)
            $table->string('size');                       // الحجم (100ml...)
            $table->text('composition');                  // التركيبة الدوائية
            $table->text('description')->nullable();      // وصف إضافي
            $table->boolean('needs_prescription')->default(false); // هل يحتاج لوصفة طبية؟
            $table->timestamps();                         // created_at و updated_at

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
