<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Only update DB once every 60 seconds to avoid unnecessary queries
            if (!$user->last_activity_at || $user->last_activity_at->diffInSeconds(now()) >= 60) {
                $user->forceFill([
                    'last_activity_at' => now(),
                    'last_login_ip' => $user->last_login_ip ?: $request->ip(),
                ])->saveQuietly();
            }
        }

        return $next($request);
    }
}
