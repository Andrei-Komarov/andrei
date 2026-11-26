<x-admin-layout title="Безопасность">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Безопасность</h1>
            <p class="text-sm text-slate-500">Двухфакторная аутентификация, IP-белый список, сессия админки и логирование входов.</p>
        </header>

        <form method="POST" action="{{ route('admin.security.security.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- 2FA -->
            <div>
                <label class="flex items-center gap-2">
                    <input
                        id="security_2fa_enabled"
                        name="security_2fa_enabled"
                        type="checkbox"
                        value="1"
                        @checked(old('security_2fa_enabled', $siteSettings->security_2fa_enabled ?? false))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Включить двухфакторную аутентификацию (2FA)</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">Требовать подтверждение через приложение-аутентификатор при входе в админ-панель.</p>
            </div>

            <hr class="border-slate-200">

            <!-- IP Whitelist -->
            <div>
                <label for="security_admin_ip_whitelist" class="block text-sm font-medium">IP-белый список для админки</label>
                <textarea
                    id="security_admin_ip_whitelist"
                    name="security_admin_ip_whitelist"
                    rows="3"
                    class="mt-1 w-full rounded border p-2 font-mono text-sm"
                >{{ old('security_admin_ip_whitelist', $siteSettings->security_admin_ip_whitelist ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Разрешить доступ к админ-панели только с указанных IP-адресов. Укажите IP-адреса через запятую (например, 192.168.1.1, 10.0.0.1). Оставьте пустым, чтобы отключить.</p>
            </div>

            <hr class="border-slate-200">

            <!-- Admin Session Lifetime -->
            <div>
                <label for="security_admin_session_lifetime" class="block text-sm font-medium">Время жизни сессии админки (минуты)</label>
                <input
                    id="security_admin_session_lifetime"
                    name="security_admin_session_lifetime"
                    type="number"
                    min="15"
                    max="10080"
                    value="{{ old('security_admin_session_lifetime', $siteSettings->security_admin_session_lifetime ?? 1440) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Через сколько минут неактивности завершается сессия админ-панели. По умолчанию: 1440 (24 часа).</p>
            </div>

            <!-- Admin Max Attempts -->
            <div>
                <label for="security_admin_max_attempts" class="block text-sm font-medium">Макс. попыток входа в админку</label>
                <input
                    id="security_admin_max_attempts"
                    name="security_admin_max_attempts"
                    type="number"
                    min="1"
                    max="20"
                    value="{{ old('security_admin_max_attempts', $siteSettings->security_admin_max_attempts ?? 5) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Количество неудачных попыток входа перед временной блокировкой. По умолчанию: 5.</p>
            </div>

            <!-- Log Admin Logins -->
            <div>
                <label class="flex items-center gap-2">
                    <input
                        id="security_log_admin_logins"
                        name="security_log_admin_logins"
                        type="checkbox"
                        value="1"
                        @checked(old('security_log_admin_logins', $siteSettings->security_log_admin_logins ?? true))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Логировать входы в админ-панель</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">Записывать все входы и выходы из админ-панели с IP-адресом и временем в журнал активности.</p>
            </div>

            @error('security_admin_ip_whitelist')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('security_admin_session_lifetime')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('security_admin_max_attempts')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки безопасности</button>
        </form>
    </section>
</x-admin-layout>
