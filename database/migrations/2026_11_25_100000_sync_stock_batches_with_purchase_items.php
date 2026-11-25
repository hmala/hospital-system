<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * مزامنة وتصحيح وجبات المخزن المرتبطة بفواتير المشتريات لضمان تطابق المادة في المخزن مع الفاتورة.
     */
    public function up(): void
    {
        if (Schema::hasTable('stock_batches') && Schema::hasTable('purchase_items')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement('
                    UPDATE stock_batches
                    SET product_id = (
                        SELECT pi.product_id
                        FROM purchase_items pi
                        WHERE pi.id = stock_batches.purchase_item_id
                    )
                    WHERE purchase_item_id IS NOT NULL
                      AND EXISTS (
                        SELECT 1
                        FROM purchase_items pi
                        WHERE pi.id = stock_batches.purchase_item_id
                          AND pi.product_id != stock_batches.product_id
                      )
                ');
            } else {
                DB::statement('
                    UPDATE stock_batches sb
                    INNER JOIN purchase_items pi ON sb.purchase_item_id = pi.id
                    SET sb.product_id = pi.product_id
                    WHERE sb.product_id != pi.product_id
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // التصحيح تصحيح للبيانات، لا يحتاج لعكسه.
    }
};
