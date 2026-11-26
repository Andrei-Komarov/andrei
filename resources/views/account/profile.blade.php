<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Профиль — Finder</title>
    @include('partials.theme-bootstrap')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <h1 class="mb-6 text-xl font-bold">Профиль</h1>

                @if (session('success'))
                    <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="max-w-md space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="avatar" class="mb-1 block text-sm font-medium text-slate-700">Аватар</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="block w-full rounded border p-2 text-sm">
                        @error('avatar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Имя</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="block w-full rounded border p-2 text-sm">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="block w-full rounded border p-2 text-sm">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить</button>
                </form>
            </section>
            </div>
        </main>
</body>
</html>
