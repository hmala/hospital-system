<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSurgeryLabRadiology extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-surgery-lab-radiology';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('اختبار نظام التحاليل والأشعة للعمليات الجراحية...');

        // اختبار العلاقات
        $surgeries = \App\Models\Surgery::with(['labTests', 'radiologyTests'])->get();
        $this->info("إجمالي العمليات: {$surgeries->count()}");

        foreach ($surgeries as $surgery) {
            $this->info("عملية: {$surgery->surgery_type}");
            $this->info("  - تحاليل مختبرية: " . $surgery->labTests->count());
            $this->info("  - فحوصات إشعاعية: " . $surgery->radiologyTests->count());
        }

        // اختبار الطلبات المعلقة
        $pendingLabTests = \App\Models\SurgeryLabTest::where('status', 'pending')->count();
        $pendingRadiologyTests = \App\Models\SurgeryRadiologyTest::where('status', 'pending')->count();

        $this->info("الطلبات المعلقة:");
        $this->info("  - تحاليل مختبرية: {$pendingLabTests}");
        $this->info("  - فحوصات إشعاعية: {$pendingRadiologyTests}");

        // اختبار الطلبات المكتملة
        $completedLabTests = \App\Models\SurgeryLabTest::where('status', 'completed')->count();
        $completedRadiologyTests = \App\Models\SurgeryRadiologyTest::where('status', 'completed')->count();

        $this->info("الطلبات المكتملة:");
        $this->info("  - تحاليل مختبرية: {$completedLabTests}");
        $this->info("  - فحوصات إشعاعية: {$completedRadiologyTests}");

        $this->info('تم الانتهاء من الاختبار بنجاح!');
    }
}
