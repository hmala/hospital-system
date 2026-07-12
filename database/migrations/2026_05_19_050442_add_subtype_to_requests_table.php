<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // إضافة عمود subtype بعد type
            $table->string('subtype', 50)->nullable()->after('type');
        });

        if (DB::getDriverName() !== 'sqlite') {
            // تحديث البيانات الموجودة: نقل radiology_category من details إلى subtype
            DB::statement("
                UPDATE requests 
                SET subtype = JSON_UNQUOTE(JSON_EXTRACT(details, '$.radiology_category'))
                WHERE type = 'radiology' 
                AND JSON_EXTRACT(details, '$.radiology_category') IS NOT NULL
            ");

            // تعيين 'general' للطلبات التي ليس لها فئة محددة
            DB::statement("
                UPDATE requests 
                SET subtype = 'general'
                WHERE type = 'radiology' 
                AND (subtype IS NULL OR subtype = '')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('subtype');
        });
    }
};
