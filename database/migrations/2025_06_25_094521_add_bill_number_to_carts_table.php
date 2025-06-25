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
    Schema::table('carts', function (Blueprint $table) {
        $table->string('bill_number')->nullable()->after('status'); // أو عدّل حسب الترتيب المناسب لك
    });
}

public function down()
{
    Schema::table('carts', function (Blueprint $table) {
        $table->dropColumn('bill_number');
    });
}

};
