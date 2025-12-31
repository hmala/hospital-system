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
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->string('main_category')->nullable()->after('id')->comment('التصنيف الرئيسي');
            $table->string('subcategory')->nullable()->after('main_category')->comment('التصنيف الفرعي');
            $table->text('notes')->nullable()->after('is_active')->comment('ملاحظات');
            
            // إزالة عمود category القديم إذا كان موجوداً
            if (Schema::hasColumn('lab_tests', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropColumn(['main_category', 'subcategory', 'notes']);
        });
    }
};
