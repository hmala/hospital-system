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
        // إضافة الحقول السريرية والفنية لجدول طلبات الخدج nicu_medical_orders
        if (Schema::hasTable('nicu_medical_orders')) {
            Schema::table('nicu_medical_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('nicu_medical_orders', 'lab_test_id')) {
                    $table->foreignId('lab_test_id')->nullable()->after('item_name')->constrained('lab_tests')->nullOnDelete();
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'radiology_type_id')) {
                    $table->foreignId('radiology_type_id')->nullable()->after('lab_test_id')->constrained('radiology_types')->nullOnDelete();
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'unit')) {
                    $table->string('unit', 50)->nullable()->after('result');
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'reference_range')) {
                    $table->string('reference_range', 100)->nullable()->after('unit');
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'result_status')) {
                    $table->string('result_status', 30)->nullable()->after('reference_range'); // normal, high, low, abnormal
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('result_status');
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'attachment_name')) {
                    $table->string('attachment_name')->nullable()->after('attachment_path');
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'technician_notes')) {
                    $table->text('technician_notes')->nullable()->after('attachment_name');
                }
                if (!Schema::hasColumn('nicu_medical_orders', 'order_group_id')) {
                    $table->string('order_group_id', 60)->nullable()->after('instructions');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nicu_medical_orders')) {
            Schema::table('nicu_medical_orders', function (Blueprint $table) {
                if (Schema::hasColumn('nicu_medical_orders', 'lab_test_id')) {
                    $table->dropForeign(['lab_test_id']);
                    $table->dropColumn('lab_test_id');
                }
                if (Schema::hasColumn('nicu_medical_orders', 'radiology_type_id')) {
                    $table->dropForeign(['radiology_type_id']);
                    $table->dropColumn('radiology_type_id');
                }
                $columnsToDrop = [
                    'unit',
                    'reference_range',
                    'result_status',
                    'attachment_path',
                    'attachment_name',
                    'technician_notes',
                    'order_group_id',
                ];
                foreach ($columnsToDrop as $col) {
                    if (Schema::hasColumn('nicu_medical_orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
