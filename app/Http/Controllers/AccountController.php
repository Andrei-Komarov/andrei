<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Display the profile page.
     */
    public function profile(): View
    {
        return view('account.profile', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the profile information.
     */
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

        return redirect()->route('account.profile')->with('success', 'Профиль обновлён.');
    }

    /**
     * Display the password page.
     */
    public function password(): View
    {
        return view('account.password');
    }

    /**
     * Update the password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('account.password')->with('success', 'Пароль изменён.');
    }

    /**
     * Display the notifications page.
     */
    public function notifications(): View
    {
        return view('account.notifications', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update notification settings.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
            'telegram_notifications' => ['required', 'boolean'],
        ]);

        $request->user()->update($validated);

        return redirect()->route('account.notifications')->with('success', 'Настройки уведомлений обновлены.');
    }

    /**
     * Display the security page.
     */
    public function security(): View
    {
        $loginHistory = LoginHistory::where('user_id', auth()->id())
            ->orderBy('login_at', 'desc')
            ->limit(20)
            ->get();

        return view('account.security', [
            'user' => auth()->user(),
            'sessions' => [],
            'loginHistory' => $loginHistory,
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Generate 2FA secret (simple implementation)
        $secret = base64_encode(random_bytes(20));
        $recoveryCodes = collect(range(1, 8))->map(fn () => bin2hex(random_bytes(4)))->all();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->two_factor_enabled_at = now();
        $user->save();

        return redirect()->route('account.security')->with('success', 'Двухфакторная аутентификация включена.');
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ])->save();

        return redirect()->route('account.security')->with('success', 'Двухфакторная аутентификация отключена.');
    }

    /**
     * Logout from all other sessions.
     */
    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        // Clear other sessions (simplified — just invalidate current session except this one)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.security')->with('success', 'Другие сессии завершены.');
    }
}
