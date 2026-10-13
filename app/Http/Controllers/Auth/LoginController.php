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
     * @var string
     */
    protected $redirectTo = '/dashboard';

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
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
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
     * @param  \Illuminate\Http\Request  $request
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
