<x-admin-layout title="Системные ошибки">
    <div class="space-y-4">
        <section class="rounded-xl bg-white p-4 shadow-sm">
            <header class="mb-4">
                <h1 class="text-2xl font-bold">Системные ошибки</h1>
                <p class="text-sm text-slate-500">Ошибки, возникшие при выполнении скриптов на сайте.</p>
            </header>

            <form method="GET" action="{{ route('admin.logs.system-errors') }}" class="grid gap-3 sm:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <label class="text-sm font-medium">
                    Тип ошибки
                    <select name="error_type" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                        <option value="">Все типы</option>
                        @foreach ($errorTypes as $errorType)
                            <option value="{{ $errorType }}" {{ request('error_type') === $errorType ? 'selected' : '' }}>{{ $errorType }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium">
                    Поиск
                    <input name="search" value="{{ request('search') }}" placeholder="Тип ошибки или сообщение" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
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
                    <a href="{{ route('admin.logs.system-errors') }}" class="rounded border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Сброс</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-3">Время</th>
                            <th class="p-3">Тип ошибки</th>
                            <th class="p-3">Сообщение</th>
                            <th class="p-3">Файл</th>
                            <th class="p-3">Строка</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 whitespace-nowrap text-slate-600">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                <td class="p-3">
                                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ $log->action }}</span>
                                </td>
                                <td class="p-3 max-w-xs">
                                    <div class="mb-1">{{ $log->description }}</div>
                                    @if ($log->details && isset($log->details['trace']))
                                        <details class="text-xs">
                                            <summary class="cursor-pointer text-blue-600 hover:underline">Стек вызовов</summary>
                                            <pre class="mt-1 max-h-60 overflow-auto rounded bg-slate-100 p-2 whitespace-pre-wrap font-mono text-xs">{{ $log->details['trace'] }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-xs text-slate-600">
                                    @if ($log->details && isset($log->details['file']))
                                        {{ \Str::after($log->details['file'], base_path('/')) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-3 text-xs text-slate-500">
                                    @if ($log->details && isset($log->details['line']))
                                        {{ $log->details['line'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500">Ошибок не найдено.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $logs->links() }}</div>
        </section>
    </div>
</x-admin-layout>
