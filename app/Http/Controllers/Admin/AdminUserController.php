<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $isConfiguredAdmin = $this->isConfiguredAdmin($user);
        $emailRules = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];

        if ($isConfiguredAdmin) {
            $emailRules[] = Rule::in([$user->email]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $passwordChanged = filled($data['password'] ?? null);
        unset($data['password_confirmation']);
        if (! $passwordChanged) {
            unset($data['password']);
        }
        $emailChanged = $user->email !== $data['email'];

        DB::transaction(function () use ($user, $data, $emailChanged, $passwordChanged): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->update($data);

            if ($emailChanged) {
                $lockedUser->email_verified_at = null;
                $lockedUser->save();
            }

            if ($passwordChanged && config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getKey())
                    ->delete();
            }
        });

        if ($emailChanged) {
            $user->refresh()->sendEmailVerificationNotification();
        }

        if ($passwordChanged && $request->user('admin')->is($user)) {
            auth('admin')->logout();
            $request->session()->regenerate();

            return to_route('admin.login')->with('success', 'Данные обновлены. Войдите снова с новым паролем.');
        }

        return to_route('admin.index')->with(
            'success',
            $emailChanged
                ? 'Данные пользователя обновлены. На новый email отправлена ссылка для подтверждения.'
                : 'Данные пользователя обновлены.',
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user('admin')->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Нельзя удалить собственную учётную запись администратора.',
            ]);
        }

        if ($this->isConfiguredAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => 'Нельзя удалить закреплённую учётную запись администратора.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->isAdmin()) {
                $administrators = User::query()
                    ->where('is_admin', true)
                    ->lockForUpdate()
                    ->get();

                if ($administrators->count() <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'Нельзя удалить последнего администратора системы.',
                    ]);
                }
            }

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getKey())
                    ->delete();
            }

            $lockedUser->notifications()->delete();
            $lockedUser->delete();
        });

        return to_route('admin.index')->with('success', 'Пользователь удалён.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['is_admin' => ['required', 'boolean']]);
        $isAdmin = (bool) $data['is_admin'];
        $isConfiguredAdmin = $this->isConfiguredAdmin($user);

        if (($isConfiguredAdmin || $request->user('admin')->is($user)) && ! $isAdmin) {
            throw ValidationException::withMessages([
                'is_admin' => 'Нельзя снять роль у закреплённого администратора или у самого себя.',
            ]);
        }

        DB::transaction(function () use ($user, $isAdmin): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $isAdmin && $lockedUser->isAdmin()) {
                $administrators = User::query()
                    ->where('is_admin', true)
                    ->lockForUpdate()
                    ->get();

                if ($administrators->count() <= 1) {
                    throw ValidationException::withMessages([
                        'is_admin' => 'В системе должен оставаться хотя бы один администратор.',
                    ]);
                }
            }

            $lockedUser->update(['is_admin' => $isAdmin]);
        });

        return to_route('admin.index')->with('success', 'Роль пользователя обновлена.');
    }

    private function isConfiguredAdmin(User $user): bool
    {
        $configuredAdminEmail = config('services.admin.email');

        return is_string($configuredAdminEmail)
            && $configuredAdminEmail !== ''
            && mb_strtolower($user->email) === mb_strtolower($configuredAdminEmail);
    }
}
