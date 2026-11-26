<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $configuredAdminEmail = config('services.admin.email');

        if (is_string($configuredAdminEmail)
            && $configuredAdminEmail !== ''
            && mb_strtolower($validated['email']) === mb_strtolower($configuredAdminEmail)) {
            throw ValidationException::withMessages([
                'email' => 'Для создания учётной записи администратора используйте команду app:promote-admin.',
            ]);
        }

        $user = User::query()->create([...$validated, 'is_admin' => false]);

        $user->sendEmailVerificationNotification();
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('verification.notice');
    }
}
