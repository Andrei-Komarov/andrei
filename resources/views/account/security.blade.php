<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Безопасность — Finder</title>
    @include('partials.theme-bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <h1 class="mb-6 text-xl font-bold">Безопасность</h1>

                @if (session('success'))
                    <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif

                <div class="space-y-6">
                    <div>
                        <h2 class="mb-2 text-base font-semibold">Двухфакторная аутентификация</h2>
                        <p class="mb-3 text-sm text-slate-500">Добавьте дополнительную защиту для вашего аккаунта.</p>

                        @if ($user->two_factor_enabled_at)
                            <div class="mb-3 flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span class="text-sm text-slate-700">Включена</span>
                            </div>
                            <form method="POST" action="{{ route('account.security.2fa.disable') }}" class="inline">
                                @csrf
                                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700" onclick="return confirm('Вы уверены, что хотите отключить двухфакторную аутентификацию?')">Отключить 2FA</button>
                            </form>
                        @else
                            <div class="mb-3 flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full bg-slate-400"></span>
                                <span class="text-sm text-slate-700">Отключена</span>
                            </div>
                            <form method="POST" action="{{ route('account.security.2fa.enable') }}" class="inline">
                                @csrf
                                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Включить 2FA</button>
                            </form>
                        @endif
                    </div>

                    <div class="border-t border-slate-200 pt-6">
                        <h2 class="mb-2 text-base font-semibold">Активные сессии</h2>
                        <p class="mb-3 text-sm text-slate-500">Управление активными сессиями на других устройствах.</p>

                        <form method="POST" action="{{ route('account.security.logout-others') }}">
                            @csrf
                            <button type="submit" class="rounded border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Вы уверены, что хотите завершить другие сессии?')">Завершить другие сессии</button>
                        </form>
                    </div>

                    <div class="border-t border-slate-200 pt-6">
                        <h2 class="mb-2 text-base font-semibold">История входов</h2>
                        <p class="mb-3 text-sm text-slate-500">Последние входы в ваш аккаунт.</p>

                        @if ($loginHistory->isEmpty())
                            <p class="text-sm text-slate-400">История входов пока пуста. Записи появятся после следующего входа.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200">
                                            <th class="pb-2 text-left font-medium text-slate-500">Дата и время</th>
                                            <th class="pb-2 text-left font-medium text-slate-500">IP-адрес</th>
                                            <th class="pb-2 text-left font-medium text-slate-500">Устройство</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($loginHistory as $entry)
                                            <tr class="border-b border-slate-100">
                                                <td class="py-2">{{ $entry->login_at->format('d.m.Y H:i:s') }}</td>
                                                <td class="py-2 font-mono text-xs">{{ $entry->ip_address ?? '—' }}</td>
                                                <td class="py-2 text-xs text-slate-500" title="{{ $entry->user_agent ?? '' }}">
                                                    {{ Str::limit($entry->user_agent ?? '—', 40) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
            </div>
        </main>
</body>
</html>
