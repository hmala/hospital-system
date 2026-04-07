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
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->string('supplier_barcode')->nullable()->after('original_barcode')->comment('باركود المورد/الشركة المصنعة');
            $table->string('manufacturer_lot_number')->nullable()->after('supplier_barcode')->comment('رقم دفعة المصنع');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropColumn(['supplier_barcode', 'manufacturer_lot_number']);
        });
    }
};
