<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Новый пароль — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-lg">
                <form method="POST" action="{{ route('password.store') }}" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                    @csrf
                    <h1 class="border-b border-slate-200 pb-4 text-center text-2xl font-bold">Создание нового пароля</h1>
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required class="w-full rounded border p-2">
                        @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Новый пароль</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full rounded border p-2">
                        @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-medium">Повторите пароль</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded border p-2">
                    </div>
                    <button class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Сохранить новый пароль</button>
                </form>
            </div>
        </main>
    </body>
</html>
