<x-admin-layout title="Редактирование пользователя">
    <div class="mx-auto max-w-2xl">
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 p-3 text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')

            <header class="border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Редактирование пользователя</h1>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </header>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Имя</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="255" required class="w-full rounded border p-3">
                @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required class="w-full rounded border p-3">
                @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <fieldset class="space-y-4 border-t pt-5">
                <legend class="text-sm font-semibold">Смена пароля — необязательно</legend>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Новый пароль</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded border p-3">
                    @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">Повторите новый пароль</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded border p-3">
                </div>
                <p class="text-xs text-slate-500">При смене пароля активные сессии пользователя будут завершены.</p>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Сохранить</button>
                <a href="{{ route('admin.index') }}" class="text-sm text-slate-600 hover:underline">Отмена</a>
            </div>
        </form>
    </div>
</x-admin-layout>
