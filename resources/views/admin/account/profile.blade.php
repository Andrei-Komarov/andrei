<x-admin-layout :title="'Профиль администратора'">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h1 class="mb-6 text-xl font-bold">Профиль</h1>

        @if (session('success'))
            <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.account.profile.update') }}" enctype="multipart/form-data" class="max-w-md space-y-4">
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
</x-admin-layout>
