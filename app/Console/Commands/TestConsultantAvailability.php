<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestConsultantAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:consultant-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار نظام توفر الأطباء الاستشاريين';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== اختبار نظام توفر الأطباء الاستشاريين ===');
        $this->newLine();

        // اختبار 1: التحقق من وجود الأطباء الاستشاريين
        $this->info('1. التحقق من الأطباء الاستشاريين:');
        try {
            $consultantDoctors = \App\Models\Doctor::where('type', 'consultant')->where('is_active', true)->get();
            $this->info("   عدد الأطباء الاستشاريين: {$consultantDoctors->count()}");

            if ($consultantDoctors->count() > 0) {
                $this->info('   عينة من الأطباء الاستشاريين:');
                foreach ($consultantDoctors->take(3) as $doctor) {
                    $availability = $doctor->is_available_today ? 'متوفر' : 'غير متوفر';
                    $this->info("     - د. {$doctor->user->name} ({$doctor->specialization}): {$availability}");
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في قراءة الأطباء الاستشاريين: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 2: التحقق من الـ routes
        $this->info('2. التحقق من الـ routes:');
        try {
            $routes = app('router')->getRoutes();
            $routesFound = [
                'consultant-availability.index' => false,
                'consultant-availability.update' => false,
                'consultant-availability.bulk-update' => false,
            ];

            foreach ($routes as $route) {
                $name = $route->getName();
                if (isset($routesFound[$name])) {
                    $routesFound[$name] = true;
                    $this->info("   ✓ {$name} - {$route->uri()} ({$route->methods()[0]})");
                }
            }

            foreach ($routesFound as $routeName => $found) {
                if (!$found) {
                    $this->error("   ✗ {$routeName} غير موجود");
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من الـ routes: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 3: التحقق من الصلاحيات
        $this->info('3. التحقق من الصلاحيات:');
        try {
            $receptionistRole = \Spatie\Permission\Models\Role::where('name', 'receptionist')->first();
            if ($receptionistRole) {
                $permissions = $receptionistRole->permissions->pluck('name')->toArray();
                if (in_array('view doctors', $permissions)) {
                    $this->info('   ✓ دور receptionist لديه صلاحية view doctors');
                } else {
                    $this->error('   ✗ دور receptionist لا يملك صلاحية view doctors');
                }
            } else {
                $this->error('   ✗ دور receptionist غير موجود');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من الصلاحيات: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 4: محاكاة تحديث توفر
        $this->info('4. اختبار تحديث التوفر:');
        try {
            $consultantDoctor = \App\Models\Doctor::where('type', 'consultant')->where('is_active', true)->first();
            if ($consultantDoctor) {
                $originalAvailability = $consultantDoctor->is_available_today;
                $newAvailability = !$originalAvailability;

                $consultantDoctor->update([
                    'is_available_today' => $newAvailability,
                    'available_date' => today(),
                ]);

                $this->info("   ✓ تم تحديث توفر د. {$consultantDoctor->user->name} من " .
                           ($originalAvailability ? 'متوفر' : 'غير متوفر') . ' إلى ' .
                           ($newAvailability ? 'متوفر' : 'غير متوفر'));

                // إعادة التوفر الأصلي
                $consultantDoctor->update([
                    'is_available_today' => $originalAvailability,
                    'available_date' => $originalAvailability ? today() : null,
                ]);
            } else {
                $this->warn('   ! لا يوجد أطباء استشاريين للاختبار');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في اختبار تحديث التوفر: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== انتهاء الاختبار ===');
    }
}
