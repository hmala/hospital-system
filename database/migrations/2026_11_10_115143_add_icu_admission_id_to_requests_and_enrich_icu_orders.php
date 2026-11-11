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
        if (Schema::hasTable('requests')) {
            Schema::table('requests', function (Blueprint $table) {
                if (! Schema::hasColumn('requests', 'icu_admission_id')) {
                    $table->foreignId('icu_admission_id')->nullable()->after('incubator_reservation_id')->constrained('icu_admissions')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('icu_medical_orders')) {
            Schema::table('icu_medical_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('icu_medical_orders', 'item_name')) {
                    $table->string('item_name')->nullable()->after('order_type');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'lab_test_id')) {
                    $table->foreignId('lab_test_id')->nullable()->after('item_name')->constrained('lab_tests')->nullOnDelete();
                }
                if (! Schema::hasColumn('icu_medical_orders', 'radiology_type_id')) {
                    $table->foreignId('radiology_type_id')->nullable()->after('lab_test_id')->constrained('radiology_types')->nullOnDelete();
                }
                if (! Schema::hasColumn('icu_medical_orders', 'price')) {
                    $table->decimal('price', 12, 2)->default(0)->after('radiology_type_id');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'order_group_id')) {
                    $table->string('order_group_id')->nullable()->after('price');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'instructions')) {
                    $table->text('instructions')->nullable()->after('order_text');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'result')) {
                    $table->text('result')->nullable()->after('status');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'unit')) {
                    $table->string('unit')->nullable()->after('result');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'reference_range')) {
                    $table->string('reference_range')->nullable()->after('unit');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('reference_range');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'attachment_name')) {
                    $table->string('attachment_name')->nullable()->after('attachment_path');
                }
                if (! Schema::hasColumn('icu_medical_orders', 'ordered_by')) {
                    $table->foreignId('ordered_by')->nullable()->after('attachment_name')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('icu_medical_orders', 'ordered_at')) {
                    $table->dateTime('ordered_at')->nullable()->after('ordered_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('requests') && Schema::hasColumn('requests', 'icu_admission_id')) {
            Schema::table('requests', function (Blueprint $table) {
                $table->dropForeign(['icu_admission_id']);
                $table->dropColumn('icu_admission_id');
            });
        }

        if (Schema::hasTable('icu_medical_orders')) {
            Schema::table('icu_medical_orders', function (Blueprint $table) {
                $cols = [
                    'item_name', 'lab_test_id', 'radiology_type_id', 'price',
                    'order_group_id', 'instructions', 'result', 'unit',
                    'reference_range', 'attachment_path', 'attachment_name',
                    'ordered_by', 'ordered_at',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('icu_medical_orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
