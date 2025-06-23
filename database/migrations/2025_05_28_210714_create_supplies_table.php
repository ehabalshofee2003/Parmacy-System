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
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('category_id');
            $table->decimal('pharmacy_price', 8, 2);      // سعر الشراء من المستودع
            $table->decimal('consumer_price', 8, 2);      // سعر البيع للمستهلك
            $table->decimal('discount', 5, 2)->nullable(); // نسبة الخصم (اختياري)
            $table->integer('stock_quantity');
            $table->integer('reorder_level')->default(10); // ✅ احذف after
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
