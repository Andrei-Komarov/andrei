<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Вход администратора — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-950 p-4 text-slate-900">
        <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">LF</span>
                <div>
                    <h1 class="text-xl font-bold">Вход администратора</h1>
                    <p class="text-sm text-slate-500">Отдельный доступ к управлению сайтом</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email администратора</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="w-full rounded border p-3">
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Пароль</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded border p-3">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border">
                    Запомнить меня
                </label>
                <button class="w-full rounded bg-blue-600 px-4 py-3 font-medium text-white hover:bg-blue-700">Войти в панель</button>
            </form>

            <a href="{{ route('login') }}" class="mt-5 block text-center text-sm text-slate-500 hover:underline">Перейти ко входу на сайт</a>
        </main>
    </body>
</html>
