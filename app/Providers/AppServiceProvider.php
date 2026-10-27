<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\BedReservation;
use App\Models\Payment;
use App\Models\Request as MedicalRequest;
use App\Models\Surgery;
use App\Models\SurgeryRadiologyTest;
use App\Models\Visit;
use App\Observers\BedReservationObserver;
use App\Observers\PaymentObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        @ini_set('memory_limit', '512M');

        if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https' || str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        // تسجيل Observers للحجوزات والمدفوعات والزيارات والنشاطات
        BedReservation::observe(BedReservationObserver::class);
        Payment::observe(PaymentObserver::class);
        Appointment::observe(\App\Observers\AppointmentObserver::class);
        Visit::observe(\App\Observers\VisitObserver::class);

        // تتبع وتسجيل الدخول والخروج في سجل الحركات
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            if ($event->user) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                    'last_activity_at' => now(),
                    'last_login_ip' => request()?->ip(),
                ])->saveQuietly();

                \App\Services\UserActivityLogger::log(
                    action: 'login',
                    category: 'auth',
                    description: 'تسجيل دخول ناجح إلى النظام',
                    subject: $event->user,
                    isSensitive: false,
                    user: $event->user
                );
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                \App\Services\UserActivityLogger::log(
                    action: 'logout',
                    category: 'auth',
                    description: 'تسجيل خروج من النظام',
                    subject: $event->user,
                    isSensitive: false,
                    user: $event->user
                );
            }
        });

        // تسجيل Blade directives لـ Spatie Permission
        Blade::if('role', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('hasrole', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('hasanyrole', function ($roles) {
            return auth()->check() && auth()->user()->hasAnyRole($roles);
        });

        Blade::if('hasallroles', function ($roles) {
            return auth()->check() && auth()->user()->hasAllRoles($roles);
        });

        Blade::if('can', function ($permission) {
            return auth()->check() && auth()->user()->can($permission);
        });

        Blade::if('haspermission', function ($permission) {
            return auth()->check() && auth()->user()->can($permission);
        });

        // الأدمن الأساسي فقط لديه تجاوز تلقائي لكافة الصلاحيات
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // مشاركة البيانات مع جميع الـ views
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();

                // إحصائيات المواعيد
                $confirmedAppointments = Appointment::where('status', 'confirmed')->count();

                // إحصائيات الزيارات
                $incompleteVisits = Visit::where('status', 'incomplete')->count();
                $doctorIncompleteVisits = 0;
                if ($user->hasRole('doctor')) {
                    $doctorIncompleteVisits = Visit::where('doctor_id', $user->id)
                        ->where('status', 'incomplete')
                        ->count();
                }

                // إحصائيات العمليات
                $pendingSurgeries = Surgery::where('status', 'pending')->count();
                $waitingSurgeries = Surgery::where('status', 'waiting')->count();

                // إحصائيات الاستعلامات (جميع الطلبات المعلقة)
                $pendingRequests = MedicalRequest::where('status', 'pending')->count();

                // إحصائيات المختبر والأشعة (تشمل مصرف الدم كجزء من المختبر)
                $pendingLab = MedicalRequest::whereIn('type', ['lab', 'blood_bank'])
                    ->where('status', 'pending')
                    ->count();
                $pendingRadiology = MedicalRequest::where('type', 'radiology')
                    ->where('status', 'pending')
                    ->count();
                $pendingSurgeryLabTests = MedicalRequest::where('type', 'surgery_lab')
                    ->where('status', 'pending')
                    ->count();
                $pendingSurgeryRadiology = SurgeryRadiologyTest::where('status', 'pending')->count();

                $view->with(compact(
                    'confirmedAppointments',
                    'incompleteVisits',
                    'doctorIncompleteVisits',
                    'pendingSurgeries',
                    'waitingSurgeries',
                    'pendingRequests',
                    'pendingLab',
                    'pendingRadiology',
                    'pendingSurgeryLabTests',
                    'pendingSurgeryRadiology'
                ));
            }
        });
    }
}
