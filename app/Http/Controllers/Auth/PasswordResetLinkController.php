<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Ссылка уже отправлена. Подождите минуту и попробуйте снова.',
            ])->onlyInput('email');
        }

        return back()->with('status', 'Если аккаунт с таким адресом существует, мы отправили ссылку для сброса пароля.');
    }
}
