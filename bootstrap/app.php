<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Programar recordatorios de actividades diariamente a las 9:00 AM
        $schedule->command('reminders:schedule-activities')->dailyAt('09:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway termina SSL en el load balancer — la red interna ya provee aislamiento
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
        ]);

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Livewire v3 usa snapshots encriptados con APP_KEY como capa de seguridad propia
        $middleware->validateCsrfTokens(except: [
            'livewire/update',
            'livewire/upload-file',
        ]);

        $middleware->web(append: [
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (PostTooLargeException $e): void {
            Log::warning('Request payload too large', [
                'message' => $e->getMessage(),
            ]);
        });

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'El archivo es demasiado grande para subirlo. Reducí el tamaño e intentá de nuevo.',
                ], 413);
            }

            return null;
        });
    })->create();
