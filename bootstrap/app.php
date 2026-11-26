<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\LogLogin;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Exceptions\MaintenanceModeException;
use Illuminate\Http\Request;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'log.activity' => LogActivity::class,
            'log.login' => LogLogin::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request): string => route(
            $request->is('admin', 'admin/*') ? 'admin.login' : 'login',
        ));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->report(function (Throwable $e) {
            if ($e instanceof MaintenanceModeException) {
                return;
            }
            if ($e instanceof Exception && str_contains($e->getMessage(), 'failed with exit code')) {
                return;
            }
            try {
                ActivityLog::create([
                    'type' => 'system_error',
                    'action' => get_class($e),
                    'description' => $e->getMessage(),
                    'details' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => Str::limit($e->getTraceAsString(), 1990),
                    ],
                    'ip_address' => null,
                    'user_agent' => null,
                    'guard' => 'system',
                ]);
            } catch (Throwable) {
                // Prevent infinite recursion when logging fails
            }
        });
    })->create();

// Apply timezone from site_settings if available
try {
    $timezone = SiteSetting::query()->value('site_timezone');
    if ($timezone) {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
} catch (Throwable) {
    // Database not available during installation/migrations
}

return $application;
