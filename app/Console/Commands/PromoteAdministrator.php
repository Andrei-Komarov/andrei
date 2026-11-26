<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PromoteAdministrator extends Command
{
    protected $signature = 'app:promote-admin';

    protected $description = 'Create or promote the configured application administrator';

    public function handle(): int
    {
        $configuredAdminEmail = config('services.admin.email');

        if (! is_string($configuredAdminEmail) || ! filter_var($configuredAdminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Укажите корректный ADMIN_EMAIL в окружении приложения.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Имя администратора'));
        $password = (string) $this->secret('Пароль администратора (не менее 12 символов)');
        $passwordConfirmation = (string) $this->secret('Повторите пароль');

        if ($name === '' || mb_strlen($name) > 255) {
            $this->components->error('Имя обязательно и должно быть не длиннее 255 символов.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 12 || ! hash_equals($password, $passwordConfirmation)) {
            $this->components->error('Пароль должен содержать не менее 12 символов, а значения должны совпадать.');

            return self::FAILURE;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($configuredAdminEmail)])
            ->first();

        if ($user === null) {
            $user = new User([
                'name' => $name,
                'email' => $configuredAdminEmail,
                'password' => Hash::make($password),
            ]);
        } else {
            $user->name = $name;
            $user->password = Hash::make($password);
        }

        $user->is_admin = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->components->info('Учётная запись администратора создана или обновлена.');

        return self::SUCCESS;
    }
}
