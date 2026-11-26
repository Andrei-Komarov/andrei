<x-admin-layout :title="'Уведомления'">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h1 class="mb-6 text-xl font-bold">Настройки уведомлений</h1>

        @if (session('success'))
            <div class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.account.notifications.update') }}" class="max-w-md space-y-4">
            @csrf
            @method('PATCH')

            <label class="flex items-center gap-3">
                <input type="checkbox" name="email_notifications" value="1" {{ $user->email_notifications ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300">
                <span class="text-sm text-slate-700">Email-уведомления</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="telegram_notifications" value="1" {{ $user->telegram_notifications ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300">
                <span class="text-sm text-slate-700">Telegram-уведомления</span>
            </label>

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить</button>
        </form>
    </section>
</x-admin-layout>
