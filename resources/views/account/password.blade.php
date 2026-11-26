<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Пароль — Finder</title>
    @include('partials.theme-bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <h1 class="mb-6 text-xl font-bold">Смена пароля</h1>

                @if (session('success'))
                    <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('account.password.update') }}" class="max-w-md space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Текущий пароль</label>
                        <input type="password" id="current_password" name="current_password" required class="block w-full rounded border p-2 text-sm">
                        @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Новый пароль</label>
                        <input type="password" id="password" name="password" required class="block w-full rounded border p-2 text-sm">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Подтвердите пароль</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required class="block w-full rounded border p-2 text-sm">
                    </div>

                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Изменить пароль</button>
                </form>
            </section>
            </div>
        </main>
</body>
</html>
