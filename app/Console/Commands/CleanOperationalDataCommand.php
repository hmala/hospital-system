<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanOperationalDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-operational-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely cleans all operational and transactional tables (visits, appointments, payments, lab/rad results, emergencies, surgeries) while preserving static core data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('هل أنت متأكد من رغبتك في تصفير كافة الجداول التشغيلية والتجريبية؟')) {
            $this->warn('تم إلغاء العملية.');
            return 0;
        }

        $this->info('بدء التصفير الآمن للجداول التشغيلية...');

        $tablesToTruncate = [
            // 1. الزيارات والمواعيد والطلبات
            'visits',
            'appointments',
            'requests',

            // 2. الحسابات والمالية والكاشير
            'financial_transactions',
            'consultation_revenues',
            'payments',
            'doctor_financial_accounts',
            'doctor_dues',

            // 3. نتائج التحاليل والأشعة
            'lab_results',
            'lab_result_sub_results',
            'lab_test_results',
            'radiology_requests',
            'radiology_results',
            'user_lab_test_stats',

            // 4. الوصفات وصرف الصيدلية
            'prescriptions',
            'prescription_items',
            'prescribed_medications',
            'pharmacy_sales',
            'pharmacy_sale_items',

            // 5. قسم الطوارئ
            'emergencies',
            'emergency_emergency_service',
            'emergency_lab_requests',
            'emergency_lab_request_tests',
            'emergency_radiology_requests',
            'emergency_radiology_request_types',
            'emergency_treatments',
            'emergency_vital_signs',
            'emergency_patients',

            // 6. العمليات الجراحية والرقود والمحطات
            'surgeries',
            'surgery_additional_operations',
            'surgery_inquiries',
            'surgery_lab_tests',
            'surgery_medical_device',
            'surgery_radiology_tests',
            'surgery_treatments',
            'surgery_type_changes',
            'anesthesia_stations',
            'surgeon_stations',
            'operating_rooms_stations',
            'recovery_stations',
            'inquiry_reception_stations',
            'bed_reservations',
            'incubator_reservations',

            // 7. الإشعارات
            'notifications',
        ];

        Schema::disableForeignKeyConstraints();

        $cleanedCount = 0;
        foreach ($tablesToTruncate as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $cleanedCount++;
                $this->line(" <fg=green>✔</> تم تصفير الجدول: {$table}");
            } else {
                $this->line(" <fg=yellow>⚠</> الجدول غير موجود (تجاوز): {$table}");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info("✅ اكتمل التصفير الآمن بنجاح لـ {$cleanedCount} جدولاً تشغيلياً.");
        $this->info("🛡️ تم الحفاظ الكامل على الجداول الأساسية: المستخدمين، الأدوار، الصلاحيات، المستشفيات، العيادات، الأطباء، أدلة الفحوصات والأسعار، والأدوية، والمرضى.");

        return 0;
    }
}
