<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Уведомления — Finder</title>
    @include('partials.theme-bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <h1 class="mb-6 text-xl font-bold">Настройки уведомлений</h1>

                @if (session('success'))
                    <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('account.notifications.update') }}" class="max-w-md space-y-4">
                    @csrf
                    @method('PATCH')

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="email_notifications" value="1" {{ $user->email_notifications ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300">
                        <span class="text-sm text-slate-700">Email-уведомления</span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="telegram_notifications" value="1" {{ $user->telegram_notifications ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300">
                        <span class="text-sm text-slate-700">Telegram-уведомления</span>
                    </label>

                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить</button>
                </form>
            </section>
            </div>
        </main>
</body>
</html>
