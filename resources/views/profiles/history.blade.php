<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>История поиска — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')
            <div class="min-w-0">
            <section class="rounded-xl bg-white shadow-sm">
                <header class="border-b border-slate-200 p-4">
                    <h1 class="text-2xl font-bold">История поиска</h1>
                    <p class="text-sm text-slate-500">Действия с профилями поиска и результаты парсинга</p>
                </header>
                @include('partials.table-column-settings', ['tableKey' => 'profile-history'])
                <div class="overflow-x-auto">
                    <table class="data-table text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                @include('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'date', 'label' => 'Дата и время', 'sort' => $sort])
                                <th class="p-3 font-medium text-slate-500">Время парсинга</th>
                                @include('partials.sortable-table-heading', ['columnKey' => 'event', 'sortKey' => 'event', 'label' => 'Событие', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'profile', 'sortKey' => 'profile', 'label' => 'Профиль', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'query', 'sortKey' => 'query', 'label' => 'Поисковый запрос', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'user', 'sortKey' => 'user', 'label' => 'Пользователь', 'sort' => $sort])
                                <th class="p-3 font-medium text-slate-500">PID</th>
                                @include('partials.sortable-table-heading', ['columnKey' => 'details', 'sortKey' => 'details', 'label' => 'Подробности', 'sort' => $sort])
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($activities as $activity)
                                <tr>
                                    <td class="p-3 text-slate-600">{{ $activity->created_at->format('d.m.Y H:i:s') }}</td>
                                    <td class="p-3 text-slate-600">{{ $activity->parsing_duration ?? '—' }}</td>
                                    <td class="p-3">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-800' => in_array($activity->action, ['created', 'parse_completed']),
                                            'bg-blue-100 text-blue-800' => $activity->action === 'updated' || $activity->action === 'parse_started',
                                            'bg-red-100 text-red-800' => in_array($activity->action, ['deleted', 'parse_failed', 'parse_killed']),
                                        ])>
                                            {{ match ($activity->action) {
                                                'created' => 'Создание',
                                                'updated' => 'Изменение',
                                                'deleted' => 'Удаление',
                                                'parse_started' => 'Парсинг запущен',
                                                'parse_completed' => 'Парсинг завершён',
                                                'parse_failed' => 'Ошибка парсинга',
                                                'parse_killed' => 'Закрыт принудительно',
                                                default => $activity->action,
                                            } }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-medium">{{ $activity->profile_name }}</td>
                                    <td class="p-3 text-slate-600">{{ $activity->searchProfile->query ?? '—' }}</td>
                                    <td class="p-3 text-slate-600">{{ $activity->user?->name ?? 'Система / гость' }}</td>
                                    <td class="p-3 font-mono text-sm text-slate-600">{{ $activity->pid ?? '—' }}</td>
                                    <td class="max-w-xl p-3 text-slate-700">
                                        {{ $activity->description }}
                                        @if (! empty($activity->metadata['changed_fields']))
                                            <div class="mt-1 text-xs text-slate-500">Изменены поля: {{ implode(', ', $activity->metadata['changed_fields']) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" data-column-empty-state class="p-8 text-center text-slate-500">История пока пуста. Здесь появятся действия с профилями и итоги парсинга.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $activities->links() }}</div>
            </section>
            </div>
        </main>
    </body>
</html>
