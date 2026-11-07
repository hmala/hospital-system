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
        // 1. تحاليل العمليات (Surgery Lab Tests)
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_lab_tests', 'is_package_included')) {
                $table->boolean('is_package_included')->default(false)->after('payment_status');
            }
            if (! Schema::hasColumn('surgery_lab_tests', 'included_deductible_price')) {
                $table->decimal('included_deductible_price', 12, 2)->nullable()->after('is_package_included');
            }
            if (! Schema::hasColumn('surgery_lab_tests', 'deduction_bearer')) {
                $table->string('deduction_bearer', 20)->nullable()->after('included_deductible_price');
            }
        });

        // 2. أشعة العمليات (Surgery Radiology Tests)
        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_radiology_tests', 'is_package_included')) {
                $table->boolean('is_package_included')->default(false)->after('payment_status');
            }
            if (! Schema::hasColumn('surgery_radiology_tests', 'included_deductible_price')) {
                $table->decimal('included_deductible_price', 12, 2)->nullable()->after('is_package_included');
            }
            if (! Schema::hasColumn('surgery_radiology_tests', 'deduction_bearer')) {
                $table->string('deduction_bearer', 20)->nullable()->after('included_deductible_price');
            }
        });

        // 3. الخدمات الإضافية للعمليات (Surgery Extra Services)
        Schema::table('surgery_extra_services', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_extra_services', 'is_package_included')) {
                $table->boolean('is_package_included')->default(false)->after('payment_status');
            }
            if (! Schema::hasColumn('surgery_extra_services', 'included_deductible_price')) {
                $table->decimal('included_deductible_price', 12, 2)->nullable()->after('is_package_included');
            }
            if (! Schema::hasColumn('surgery_extra_services', 'deduction_bearer')) {
                $table->string('deduction_bearer', 20)->nullable()->after('included_deductible_price');
            }
        });

        // 4. العمليات الجراحية نفسها (شمول الغرفة بالباقة والمتحمل)
        Schema::table('surgeries', function (Blueprint $table) {
            if (! Schema::hasColumn('surgeries', 'is_room_package_included')) {
                $table->boolean('is_room_package_included')->default(false)->after('room_fee_paid_amount');
            }
            if (! Schema::hasColumn('surgeries', 'room_deduction_bearer')) {
                $table->string('room_deduction_bearer', 20)->nullable()->after('is_room_package_included');
            }
        });

        // 5. المدفوعات (تخزين إجمالي استقطاعات الجراح واللقطة التفصيلية للباقة)
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'doctor_deductions_amount')) {
                $table->decimal('doctor_deductions_amount', 12, 2)->default(0)->after('insurance_share');
            }
            if (! Schema::hasColumn('payments', 'included_package_items')) {
                $table->json('included_package_items')->nullable()->after('doctor_deductions_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'included_package_items')) {
                $table->dropColumn('included_package_items');
            }
            if (Schema::hasColumn('payments', 'doctor_deductions_amount')) {
                $table->dropColumn('doctor_deductions_amount');
            }
        });

        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'room_deduction_bearer')) {
                $table->dropColumn('room_deduction_bearer');
            }
            if (Schema::hasColumn('surgeries', 'is_room_package_included')) {
                $table->dropColumn('is_room_package_included');
            }
        });

        Schema::table('surgery_extra_services', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_extra_services', 'deduction_bearer')) {
                $table->dropColumn('deduction_bearer');
            }
            if (Schema::hasColumn('surgery_extra_services', 'included_deductible_price')) {
                $table->dropColumn('included_deductible_price');
            }
            if (Schema::hasColumn('surgery_extra_services', 'is_package_included')) {
                $table->dropColumn('is_package_included');
            }
        });

        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_radiology_tests', 'deduction_bearer')) {
                $table->dropColumn('deduction_bearer');
            }
            if (Schema::hasColumn('surgery_radiology_tests', 'included_deductible_price')) {
                $table->dropColumn('included_deductible_price');
            }
            if (Schema::hasColumn('surgery_radiology_tests', 'is_package_included')) {
                $table->dropColumn('is_package_included');
            }
        });

        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_lab_tests', 'deduction_bearer')) {
                $table->dropColumn('deduction_bearer');
            }
            if (Schema::hasColumn('surgery_lab_tests', 'included_deductible_price')) {
                $table->dropColumn('included_deductible_price');
            }
            if (Schema::hasColumn('surgery_lab_tests', 'is_package_included')) {
                $table->dropColumn('is_package_included');
            }
        });
    }
};
