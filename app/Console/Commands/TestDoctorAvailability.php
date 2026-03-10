<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDoctorAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:doctor-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار نظام التوفر اليومي للأطباء';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== اختبار نظام التوفر اليومي للأطباء ===');
        $this->newLine();

        // اختبار 1: التحقق من وجود الحقول الجديدة
        $this->info('1. التحقق من الحقول الجديدة في جدول doctors:');
        try {
            $columns = \DB::select("DESCRIBE doctors");
            $hasIsAvailableToday = false;
            $hasAvailableDate = false;

            foreach ($columns as $column) {
                if ($column->Field === 'is_available_today') {
                    $hasIsAvailableToday = true;
                    $this->info("   ✓ حقل is_available_today موجود (النوع: {$column->Type})");
                }
                if ($column->Field === 'available_date') {
                    $hasAvailableDate = true;
                    $this->info("   ✓ حقل available_date موجود (النوع: {$column->Type})");
                }
            }

            if (!$hasIsAvailableToday) {
                $this->error('   ✗ حقل is_available_today غير موجود');
            }
            if (!$hasAvailableDate) {
                $this->error('   ✗ حقل available_date غير موجود');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من الحقول: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 2: التحقق من وجود أطباء
        $this->info('2. التحقق من وجود أطباء في النظام:');
        try {
            $doctorsCount = \App\Models\Doctor::count();
            $this->info("   عدد الأطباء: {$doctorsCount}");

            if ($doctorsCount > 0) {
                $sampleDoctor = \App\Models\Doctor::first();
                $doctorName = $sampleDoctor->user ? $sampleDoctor->user->name : 'غير محدد';
                $this->info("   عينة من طبيب: د. {$doctorName}");
                $availability = $sampleDoctor->is_available_today ? 'متوفر' : 'غير متوفر';
                $this->info("   التوفر الحالي: {$availability}");
                $lastUpdate = $sampleDoctor->available_date ?? 'غير محدد';
                $this->info("   تاريخ آخر تحديث: {$lastUpdate}");
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في قراءة الأطباء: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 3: التحقق من الـ route
        $this->info('3. التحقق من الـ route:');
        try {
            $routes = app('router')->getRoutes();
            $availabilityRouteExists = false;

            foreach ($routes as $route) {
                if ($route->getName() === 'doctors.update-availability') {
                    $availabilityRouteExists = true;
                    $this->info('   ✓ route doctors.update-availability موجود');
                    $this->info("   URI: {$route->uri()}");
                    $this->info('   Methods: ' . implode(', ', $route->methods()));
                    break;
                }
            }

            if (!$availabilityRouteExists) {
                $this->error('   ✗ route doctors.update-availability غير موجود');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من الـ routes: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== انتهاء الاختبار ===');
    }
}
