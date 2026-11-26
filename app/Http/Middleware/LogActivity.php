<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    protected array $excludedMethods = ['GET', 'HEAD'];

    protected array $excludedPaths = [
        'notifications',
        'favicon',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log only mutating actions
        if (in_array($request->method(), $this->excludedMethods)) {
            return $response;
        }

        // Skip excluded paths
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path.'*')) {
                return $response;
            }
        }

        $guard = $request->is('admin*') ? 'admin' : 'web';
        $user = Auth::guard($guard)->user();

        $action = $request->method().': '.$request->route()?->getName();
        $description = $this->resolveDescription($request, $guard);

        ActivityLog::create([
            'user_id' => $user?->id,
            'type' => 'user_action',
            'action' => $action,
            'description' => $description,
            'details' => $request->only(['_method'] ?? []),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'guard' => $guard,
        ]);

        return $response;
    }

    protected function resolveDescription(Request $request, string $guard): string
    {
        $routeName = $request->route()?->getName();

        return match (true) {
            str_contains($routeName ?? '', 'store') => 'Создал запись',
            str_contains($routeName ?? '', 'update') => 'Обновил запись',
            str_contains($routeName ?? '', 'destroy') || str_contains($routeName ?? '', 'delete') => 'Удалил запись',
            str_contains($routeName ?? '', 'restore') => 'Восстановил запись',
            str_contains($routeName ?? '', 'bulk') => 'Массовое действие',
            str_contains($routeName ?? '', 'export') => 'Экспортировал данные',
            str_contains($routeName ?? '', 'parse') => 'Запустил парсинг',
            str_contains($routeName ?? '', 'role') => 'Изменил роль пользователя',
            str_contains($routeName ?? '', 'settings') => 'Изменил настройки',
            str_contains($routeName ?? '', 'login') => 'Войшёл в систему',
            str_contains($routeName ?? '', 'logout') => 'Выйшел из системы',
            str_contains($routeName ?? '', 'register') => 'Зарегистрировался',
            default => 'Выполнил действие: '.$routeName,
        };
    }
}
