<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      Schema::create('medicines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name_en');                    // الاسم الأجنبي
            $table->string('name_ar');                    // الاسم العربي
            $table->string('barcode')->unique();          // رقم الباركود
            $table->unsignedBigInteger('category_id'); // مفتاح أجنبي لصنف الدواء
            $table->string('image_url')->nullable();
            $table->string('manufacturer');               // اسم الشركة المصنعة
            $table->decimal('pharmacy_price', 8, 2);      // سعر الشراء من المستودع
            $table->decimal('consumer_price', 8, 2);      // سعر البيع للمستهلك
            $table->decimal('discount', 5, 2)->nullable(); // نسبة الخصم (اختياري)
            $table->integer('stock_quantity');            // الكمية بالمخزن
            $table->date('expiry_date');                  // تاريخ انتهاء الصلاحية
            $table->text('composition');                  // التركيبة الدوائية
            $table->boolean('needs_prescription')->default(false); // هل يحتاج لوصفة طبية؟
            $table->integer('reorder_level')->default(10);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null'); // قيد أجنبي
            $table->timestamps();

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
