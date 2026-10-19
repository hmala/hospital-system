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
        // 1. تحديث جدول باقات وخدمات الخدج
        if (Schema::hasTable('nicu_services')) {
            Schema::table('nicu_services', function (Blueprint $table) {
                if (!Schema::hasColumn('nicu_services', 'hi_price')) {
                    $table->decimal('hi_price', 12, 2)->default(0)->after('daily_fee')->comment('تسعيرة هيئة الضمان الصحي المعتمدة');
                }
                if (!Schema::hasColumn('nicu_services', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price')->comment('هل الباقة مشمولة بالضمان الصحي');
                }
                if (!Schema::hasColumn('nicu_services', 'official_claim_name')) {
                    $table->string('official_claim_name')->nullable()->after('name')->comment('المسمى الرسمي في مطالبات الضمان');
                }
                if (!Schema::hasColumn('nicu_services', 'hotel_fee')) {
                    $table->decimal('hotel_fee', 12, 2)->default(0)->after('description')->comment('حصة الإقامة الفندقية والسرير (تفكيك مستقبلي)');
                }
                if (!Schema::hasColumn('nicu_services', 'nursing_fee')) {
                    $table->decimal('nursing_fee', 12, 2)->default(0)->after('hotel_fee')->comment('حصة الرعاية التمريضية والطبية (تفكيك مستقبلي)');
                }
                if (!Schema::hasColumn('nicu_services', 'device_fee')) {
                    $table->decimal('device_fee', 12, 2)->default(0)->after('nursing_fee')->comment('حصة الجهاز والدعم التنفسي (تفكيك مستقبلي)');
                }
            });
        }

        // 2. تحديث جدول حجوزات الخدج
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('incubator_reservations', 'insurance_type')) {
                    $table->string('insurance_type', 50)->default('none')->after('status')->comment('نوع التأمين والضمان: none, hi, moi');
                }
                if (!Schema::hasColumn('incubator_reservations', 'policy_category')) {
                    $table->string('policy_category', 50)->nullable()->after('insurance_type')->comment('فئة الضمان: الفئة E, الفئة H, الفئة A');
                }
                if (!Schema::hasColumn('incubator_reservations', 'encounter_number')) {
                    $table->string('encounter_number', 100)->nullable()->after('policy_category')->comment('رقم الزيارة / المطالبة لدى هيئة الضمان');
                }
                if (!Schema::hasColumn('incubator_reservations', 'insurance_card_no')) {
                    $table->string('insurance_card_no', 100)->nullable()->after('encounter_number')->comment('رقم بطاقة أو دفتر الضمان');
                }
                if (!Schema::hasColumn('incubator_reservations', 'patient_share_amount')) {
                    $table->decimal('patient_share_amount', 12, 2)->default(0)->after('total_cost')->comment('إجمالي حصة المريض المطلوبة نقداً');
                }
                if (!Schema::hasColumn('incubator_reservations', 'insurance_share_amount')) {
                    $table->decimal('insurance_share_amount', 12, 2)->default(0)->after('patient_share_amount')->comment('إجمالي حصة الضمان الصحي المطالب بها');
                }
                if (!Schema::hasColumn('incubator_reservations', 'coverage_summary')) {
                    $table->json('coverage_summary')->nullable()->after('insurance_share_amount')->comment('ملخص تفصيلي لشرائح التغطية اليومية');
                }
            });
        }

        // 3. تحديث جدول سجلات تتبع الخدمات
        if (Schema::hasTable('nicu_service_logs')) {
            Schema::table('nicu_service_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('nicu_service_logs', 'patient_share')) {
                    $table->decimal('patient_share', 12, 2)->default(0)->after('total_cost');
                }
                if (!Schema::hasColumn('nicu_service_logs', 'insurance_share')) {
                    $table->decimal('insurance_share', 12, 2)->default(0)->after('patient_share');
                }
                if (!Schema::hasColumn('nicu_service_logs', 'coverage_percentage')) {
                    $table->decimal('coverage_percentage', 5, 2)->default(0)->after('insurance_share');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nicu_services')) {
            Schema::table('nicu_services', function (Blueprint $table) {
                $table->dropColumn([
                    'hi_price',
                    'is_hi_active',
                    'official_claim_name',
                    'hotel_fee',
                    'nursing_fee',
                    'device_fee',
                ]);
            });
        }

        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $table->dropColumn([
                    'insurance_type',
                    'policy_category',
                    'encounter_number',
                    'insurance_card_no',
                    'patient_share_amount',
                    'insurance_share_amount',
                    'coverage_summary',
                ]);
            });
        }

        if (Schema::hasTable('nicu_service_logs')) {
            Schema::table('nicu_service_logs', function (Blueprint $table) {
                $table->dropColumn([
                    'patient_share',
                    'insurance_share',
                    'coverage_percentage',
                ]);
            });
        }
    }
};
