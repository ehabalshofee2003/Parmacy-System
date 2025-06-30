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
  Schema::create('stock_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
    $table->enum('report_type', ['daily', 'weekly', 'monthly']);
    $table->integer('expiring_soon');    // عدد الأدوية التي ستنتهي قريباً
    $table->integer('low_stock');        // عدد الأدوية ذات الكمية المنخفضة
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reports');
    }
};
