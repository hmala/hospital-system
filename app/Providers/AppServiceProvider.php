<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Surgery;
use App\Models\Request as MedicalRequest;
use App\Models\BedReservation;
use App\Observers\BedReservationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // تسجيل Observer لمزامنة حالة الغرف مع الحجوزات
        BedReservation::observe(BedReservationObserver::class);

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

        // الأدمن لديه صلاحيات كاملة لكل شيء تلقائياً
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
                
                // إحصائيات المختبر والأشعة
                $pendingLab = MedicalRequest::where('type', 'lab')
                    ->where('status', 'pending')
                    ->count();
                $pendingRadiology = MedicalRequest::where('type', 'radiology')
                    ->where('status', 'pending')
                    ->count();
                $pendingSurgeryLabTests = MedicalRequest::where('type', 'surgery_lab')
                    ->where('status', 'pending')
                    ->count();
                
                $view->with(compact(
                    'confirmedAppointments',
                    'incompleteVisits',
                    'doctorIncompleteVisits',
                    'pendingSurgeries',
                    'waitingSurgeries',
                    'pendingRequests',
                    'pendingLab',
                    'pendingRadiology',
                    'pendingSurgeryLabTests'
                ));
            }
        });
    }
}
