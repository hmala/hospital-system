<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    public function redirectTo()
    {
        $user = auth()->user();

        if ($user && ($user->hasRole(['الجراح', 'surgeon']) || ($user->doctor && $user->doctor->type === 'surgeon') || in_array($user->name, ['حساب الجراحين', 'حساب الجراحين العام']) || $user->email === 'gh@hospital.com')) {
            return route('surgeon-station.index');
        }

        if ($user && ($user->hasRole(['التخدير', 'anesthesiologist']) || ($user->doctor && ($user->doctor->type === 'anesthesiologist' || $user->doctor->type === 'anesthesia')) || in_array($user->name, ['حساب أطباء التخدير', 'حساب اطباء التخدير', 'جساب اطباء التخدير', 'حساب التخدير']) || $user->email === 'anth@hospital.com')) {
            return route('anesthesia-station.index');
        }

        if ($user && $user->hasRole('doctor')) {
            return route('doctor.visits.index');
        }

        if ($user && $user->hasRole('thaman')) {
            return route('patients.index');
        }

        if ($user && ($user->hasRole(['nurse', 'تمريض']) || $user->user_type === 'nurse')) {
            return route('nursing-station.index');
        }

        if ($user && ($user->hasRole(['surgery_staff', 'موظف عمليات']) || $user->user_type === 'surgery_staff')) {
            return route('surgeries.index');
        }

        return '/dashboard';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The user has been authenticated.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        $isAdminOrHeadNurse = $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'head_nurse'])
            || $user->can('manage nurse availability');

        $isNurse = $user->hasAnyRole(['nurse', 'nicu_nurse', 'icu_nurse', 'تمريض'])
            || ($user->role ?? '') === 'nurse';

        if (! $isAdminOrHeadNurse && $isNurse && ! $user->isNurseAvailableToday()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'عذراً، حسابك غير مدرج في جدول دوام التمريض لهذا اليوم. يرجى مراجعة مسؤول قسم التمريض لتفعيل دوامك.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_activity_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_logout_at' => null,
        ])->saveQuietly();

        \App\Services\UserActivityLogger::log(
            action: 'login',
            category: 'auth',
            description: 'تسجيل دخول ناجح إلى النظام',
            subject: $user,
            isSensitive: false,
            user: $user
        );
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(\Illuminate\Http\Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user) {
            \App\Services\UserActivityLogger::log(
                action: 'logout',
                category: 'auth',
                description: 'تسجيل خروج من النظام',
                subject: $user,
                isSensitive: false,
                user: $user
            );

            // تثبيت وقت تسجيل الخروج الفعلي
            $user->forceFill([
                'last_logout_at' => now(),
            ])->saveQuietly();
        }

        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new \Illuminate\Http\JsonResponse([], 204)
            : redirect('/login');
    }
}
