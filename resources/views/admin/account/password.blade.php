<x-admin-layout :title="'Смена пароля'">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h1 class="mb-6 text-xl font-bold">Смена пароля</h1>

        @if (session('success'))
            <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.account.password.update') }}" class="max-w-md space-y-4">
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
</x-admin-layout>
