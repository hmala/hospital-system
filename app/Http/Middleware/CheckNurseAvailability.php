<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckNurseAvailability
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || $request->routeIs('login', 'logout')) {
            return $next($request);
        }

        $isAdminOrHeadNurse = $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'head_nurse'])
            || $user->can('manage nurse availability');

        $isNurse = $user->hasAnyRole(['nurse', 'nicu_nurse', 'icu_nurse', 'تمريض'])
            || ($user->role ?? '') === 'nurse';

        if (! $isAdminOrHeadNurse && $isNurse) {
            if (! $user->isNurseAvailableToday()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'عذراً، حسابك غير مدرج في جدول دوام التمريض لهذا اليوم. يرجى مراجعة مسؤول قسم التمريض.',
                ]);
            }
        }

        return $next($request);
    }
}
