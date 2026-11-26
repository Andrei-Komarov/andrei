<?php

namespace App\Http\Middleware;

use App\Models\LoginHistory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Записываем вход только при первом запросе после авторизации
        if ($request->user() && Session::isStarted() && $request->session()->has('auth.login')) {
            LoginHistory::create([
                'user_id' => $request->user()->id,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit($request->userAgent(), 500),
                'action' => 'login',
                'details' => $request->user()->email,
            ]);

            $request->session()->forget('auth.login');
        }

        return $next($request);
    }
}
