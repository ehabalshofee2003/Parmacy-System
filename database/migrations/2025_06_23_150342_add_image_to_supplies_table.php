<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('supplies', function (Blueprint $table) {
        $table->string('image')->nullable()->after('stock_quantity');
    });
}

public function down()
{
    Schema::table('supplies', function (Blueprint $table) {
        $table->dropColumn('image');
    });
}

};
