<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Вход — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-lg">
            @if (session('status'))
                <div class="mb-4 rounded bg-emerald-100 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                @csrf
                <h1 class="border-b border-slate-200 pb-4 text-center text-2xl font-bold">Вход</h1>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded border p-2">
                    @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Пароль</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded border p-2">
                    @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1" class="rounded border">
                    Запомнить меня
                </label>
                <p class="text-right text-sm"><a href="{{ route('password.request') }}" class="font-medium text-blue-700 hover:underline">Забыли пароль?</a></p>
                <button class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Войти</button>
                <p class="text-center text-sm text-slate-600">Нет аккаунта? <a href="{{ route('register') }}" class="font-medium text-blue-700 hover:underline">Зарегистрироваться</a></p>
            </form>
            </div>
        </main>
    </body>
</html>
