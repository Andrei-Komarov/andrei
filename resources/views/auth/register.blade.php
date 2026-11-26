<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Регистрация — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-lg">
            @if (session('status'))
                <div class="mb-4 rounded bg-emerald-100 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('register.store') }}" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                @csrf
                <h1 class="border-b border-slate-200 pb-4 text-center text-2xl font-bold">Регистрация</h1>
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">Имя</label>
                    <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="255" class="w-full rounded border p-2">
                    @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded border p-2">
                    @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Пароль</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full rounded border p-2">
                    @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">Повторите пароль</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded border p-2">
                </div>
                <button class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Создать аккаунт</button>
                <p class="text-center text-sm text-slate-600">Уже есть аккаунт? <a href="{{ route('login') }}" class="font-medium text-blue-700 hover:underline">Войти</a></p>
            </form>
            </div>
        </main>
    </body>
</html>
