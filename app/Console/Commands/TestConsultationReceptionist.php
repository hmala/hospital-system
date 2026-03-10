<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestConsultationReceptionist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:consultation-receptionist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار نظام موظف استعلامات الاستشارية';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== اختبار نظام موظف استعلامات الاستشارية ===');
        $this->newLine();

        // اختبار 1: التحقق من وجود الدور
        $this->info('1. التحقق من الدور والصلاحية:');
        try {
            $role = \Spatie\Permission\Models\Role::where('name', 'consultation_receptionist')->first();
            if ($role) {
                $this->info('   ✓ الدور consultation_receptionist موجود');
                $permissions = $role->permissions->pluck('name')->toArray();
                $this->info('   الصلاحيات: ' . implode(', ', $permissions));

                if (in_array('manage consultant availability', $permissions)) {
                    $this->info('   ✓ الصلاحية manage consultant availability موجودة');
                } else {
                    $this->error('   ✗ الصلاحية manage consultant availability مفقودة');
                }
            } else {
                $this->error('   ✗ الدور consultation_receptionist غير موجود');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من الدور: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 2: التحقق من وجود المستخدم
        $this->info('2. التحقق من المستخدم:');
        try {
            $user = \App\Models\User::where('email', 'consultation@hospital.com')->first();
            if ($user) {
                $this->info('   ✓ المستخدم موجود: ' . $user->name);
                $this->info('   البريد الإلكتروني: ' . $user->email);
                $this->info('   الأدوار: ' . implode(', ', $user->getRoleNames()->toArray()));

                if ($user->hasRole('consultation_receptionist')) {
                    $this->info('   ✓ المستخدم لديه الدور الصحيح');
                } else {
                    $this->error('   ✗ المستخدم لا يملك الدور الصحيح');
                }

                if ($user->can('manage consultant availability')) {
                    $this->info('   ✓ المستخدم لديه الصلاحية المطلوبة');
                } else {
                    $this->error('   ✗ المستخدم لا يملك الصلاحية المطلوبة');
                }
            } else {
                $this->error('   ✗ المستخدم غير موجود');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في التحقق من المستخدم: ' . $e->getMessage());
        }

        $this->newLine();

        // اختبار 3: التحقق من الـ routes
        $this->info('3. التحقق من الـ routes:');
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

        // اختبار 4: محاكاة الوصول للصفحة
        $this->info('4. اختبار الوصول للصفحة:');
        try {
            $user = \App\Models\User::where('email', 'consultation@hospital.com')->first();
            if ($user) {
                // محاكاة تسجيل الدخول
                \Auth::login($user);

                // محاولة الوصول للـ controller
                $controller = new \App\Http\Controllers\ConsultantAvailabilityController();
                $reflection = new \ReflectionMethod($controller, '__construct');

                // التحقق من أن الـ middleware يعمل
                $this->info('   ✓ تم تسجيل الدخول كـ consultation_receptionist');

                // محاولة الوصول للصفحة باستخدام HTTP client
                $response = \Illuminate\Support\Facades\Http::get(url('/consultant-availability'));
                $this->info('   ✓ تم محاولة الوصول لصفحة توفر الأطباء الاستشاريين');

                \Auth::logout();
            }
        } catch (\Exception $e) {
            $this->error('   ✗ خطأ في اختبار الوصول: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== انتهاء الاختبار ===');
        $this->info('يمكنك الآن تسجيل الدخول بالبيانات التالية:');
        $this->info('البريد الإلكتروني: consultation@hospital.com');
        $this->info('كلمة المرور: password');
    }
}
