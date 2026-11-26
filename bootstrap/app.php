<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            guests: '/login',
            users: function ($request) {
                $user = $request->user();
                if ($user) {
                    if ($user->isInquiryStaff()) {
                        return route('inquiry.create');
                    }
                    if ($user->hasRole(['الجراح', 'surgeon']) || ($user->doctor && $user->doctor->type === 'surgeon') || in_array($user->name, ['حساب الجراحين', 'حساب الجراحين العام']) || $user->email === 'gh@hospital.com') {
                        return route('surgeon-station.index');
                    }
                    if ($user->hasRole(['التخدير', 'anesthesiologist']) || ($user->doctor && ($user->doctor->type === 'anesthesiologist' || $user->doctor->type === 'anesthesia')) || in_array($user->name, ['حساب أطباء التخدير', 'حساب اطباء التخدير', 'جساب اطباء التخدير', 'حساب التخدير']) || $user->email === 'anth@hospital.com') {
                        return route('anesthesia-station.index');
                    }
                    if ($user->hasRole('doctor')) {
                        return route('doctor.visits.index');
                    }
                    if ($user->hasRole('thaman')) {
                        return route('patients.index');
                    }
                    if ($user->hasRole(['nurse', 'nicu_nurse', 'icu_nurse', 'تمريض']) || ($user->role ?? '') === 'nurse') {
                        return route('nursing-station.index');
                    }
                    if ($user->hasRole(['surgery_staff', 'موظف عمليات']) || $user->user_type === 'surgery_staff') {
                        return route('surgeries.index');
                    }
                }

                return '/dashboard';
            }
        );

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackUserActivity::class,
            \App\Http\Middleware\CheckNurseAvailability::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.nurse.availability' => \App\Http\Middleware\CheckNurseAvailability::class,
        ]);

        // استثناء CSRF للـ API routes
        $middleware->validateCsrfTokens(except: [
            'api/consultant-availability/bulk-update',
            'api/telegram/webhook',
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

$app->resolving(\Illuminate\Console\Command::class, function ($command, $app) {
    $command->setLaravel($app);
});

return $app;
