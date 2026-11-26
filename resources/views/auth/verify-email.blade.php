<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Подтверждение email — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        <main class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-lg">
                @if (session('status') === 'verification-link-sent')
                    <div class="mb-4 rounded bg-emerald-100 p-3 text-sm text-emerald-800">Новая ссылка для подтверждения отправлена на ваш email.</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <section class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                    <h1 class="border-b border-slate-200 pb-4 text-center text-2xl font-bold">Подтвердите email</h1>
                    <p class="text-sm text-slate-700">Мы отправили ссылку подтверждения на <strong>{{ auth()->user()->email }}</strong>. Подтвердите адрес, чтобы пользоваться сайтом.</p>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Отправить письмо ещё раз</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded border px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Выйти</button>
                    </form>
                </section>
            </div>
        </main>
    </body>
</html>
