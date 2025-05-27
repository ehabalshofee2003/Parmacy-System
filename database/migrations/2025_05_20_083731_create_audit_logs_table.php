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
        Schema::create('audit_logs', function (Blueprint $table) {
          $table->id(); // id
            $table->unsignedBigInteger('user_id'); // معرف المستخدم
            $table->string('action', 50); // نوع العملية
            $table->string('table_name', 50); // اسم الجدول
            $table->unsignedBigInteger('record_id'); // معرف السجل المتأثر
            $table->json('old_data')->nullable(); // البيانات القديمة
            $table->json('new_data')->nullable(); // البيانات الجديدة
            $table->string('ip_address', 45)->nullable(); // عنوان الـ IP (يمكن IPv6)
            $table->string('user_agent')->nullable(); // معلومات الجهاز/المتصفح
            $table->timestamp('created_at')->useCurrent(); // توقيت العملية

            // إضافة مفتاح خارجي إلى جدول المستخدمين (اختياري حسب اسم جدول المستخدمين عندك)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
