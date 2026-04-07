<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->string('original_barcode')->nullable()->after('internal_barcode');
        });
    }

    public function down()
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropColumn('original_barcode');
        });
    }
};
