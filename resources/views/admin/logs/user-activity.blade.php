<x-admin-layout title="Журнал действий пользователей">
    <div class="space-y-4">
        <section class="rounded-xl bg-white p-4 shadow-sm">
            <header class="mb-4">
                <h1 class="text-2xl font-bold">Журнал действий пользователей</h1>
                <p class="text-sm text-slate-500">Все действия пользователей на сайте и в админ-панели.</p>
            </header>

            <form method="GET" action="{{ route('admin.logs.user-activity') }}" class="grid gap-3 sm:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <label class="text-sm font-medium">
                    Тип действия
                    <select name="action_type" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                        <option value="">Все типы</option>
                        @foreach ($actionTypes as $actionType)
                            <option value="{{ $actionType }}" {{ request('action_type') === $actionType ? 'selected' : '' }}>{{ $actionType }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium">
                    Поиск
                    <input name="search" value="{{ request('search') }}" placeholder="По описанию" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <label class="text-sm font-medium">
                    Охрана
                    <select name="guard" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                        <option value="">Все</option>
                        <option value="web" @selected(request('guard') === 'web')">Сайт (web)</option>
                        <option value="admin" @selected(request('guard') === 'admin')">Админка (admin)</option>
                    </select>
                </label>
                <label class="text-sm font-medium">
                    С даты
                    <input name="date_from" type="date" value="{{ request('date_from') }}" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <label class="text-sm font-medium">
                    По дату
                    <input name="date_to" type="date" value="{{ request('date_to') }}" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Фильтр</button>
                    <a href="{{ route('admin.logs.user-activity') }}" class="rounded border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Сброс</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-3">Время</th>
                            <th class="p-3">Пользователь</th>
                            <th class="p-3">Охрана</th>
                            <th class="p-3">Действие</th>
                            <th class="p-3">Описание</th>
                            <th class="p-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 whitespace-nowrap text-slate-600">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                <td class="p-3">
                                    @if ($log->user)
                                        <span class="font-medium">{{ $log->user->name }}</span>
                                        <span class="block text-xs text-slate-400">{{ $log->user->email }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if ($log->guard === 'admin')
                                        <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Админка</span>
                                    @else
                                        <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Сайт</span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-xs text-slate-600">{{ $log->action }}</td>
                                <td class="p-3">{{ $log->description }}</td>
                                <td class="p-3 text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-slate-500">Записей не найдено.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $logs->links() }}</div>
        </section>
    </div>
</x-admin-layout>
