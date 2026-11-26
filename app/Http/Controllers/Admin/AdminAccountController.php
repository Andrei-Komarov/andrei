<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    public function profile(): View
    {
        return view('admin.account.profile', [
            'user' => auth('admin')->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                unlink(storage_path('app/public/'.$user->avatar_path));
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('admin.account.profile')->with('success', 'Профиль обновлён.');
    }

    public function password(): View
    {
        return view('admin.account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.account.password')->with('success', 'Пароль изменён.');
    }

    public function notifications(): View
    {
        return view('admin.account.notifications', [
            'user' => auth('admin')->user(),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
            'telegram_notifications' => ['required', 'boolean'],
        ]);

        $request->user()->update($validated);

        return redirect()->route('admin.account.notifications')->with('success', 'Настройки уведомлений обновлены.');
    }

    public function security(): View
    {
        $loginHistory = LoginHistory::where('user_id', auth('admin')->id())
            ->orderBy('login_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.account.security', [
            'user' => auth('admin')->user(),
            'sessions' => [],
            'loginHistory' => $loginHistory,
        ]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        $secret = base64_encode(random_bytes(20));
        $recoveryCodes = collect(range(1, 8))->map(fn () => bin2hex(random_bytes(4)))->all();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->two_factor_enabled_at = now();
        $user->save();

        return redirect()->route('admin.account.security')->with('success', 'Двухфакторная аутентификация включена.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ])->save();

        return redirect()->route('admin.account.security')->with('success', 'Двухфакторная аутентификация отключена.');
    }

    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.account.security')->with('success', 'Другие сессии завершены.');
    }
}
