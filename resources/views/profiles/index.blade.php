<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Профили поиска — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')
            <div class="min-w-0">
            @if (session('success'))
                <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
            @endif

            <section class="rounded-xl bg-white shadow-sm">
                <header class="border-b border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">Профили поиска</h1>
                            <p class="text-sm text-slate-500">Всего профилей: {{ $profiles->count() }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" data-profiles-search-input placeholder="Поиск по названию…" class="rounded border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                            <button type="button" data-open-create-dialog class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Добавить</button>
                        </div>
                    </div>
                    <div class="mx-4 mt-4 mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center gap-2 text-sm text-slate-700">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                            <span class="font-medium">Дневной лимит eBay API: {{ number_format($apiUsage['limit'], 0, '', ' ') }} запросов</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full {{ $apiUsage['percentage'] > 90 ? 'bg-red-500' : ($apiUsage['percentage'] > 70 ? 'bg-amber-500' : 'bg-blue-500') }}" style="width: {{ min($apiUsage['percentage'], 100) }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                            <span>Использовано: {{ number_format($apiUsage['used'], 0, '', ' ') }} запросов ({{ $apiUsage['percentage'] }}%)</span>
                            <span>Осталось: ~{{ number_format($apiUsage['remaining'], 0, '', ' ') }}</span>
                        </div>
                    </div>
                </header>
                @include('partials.table-column-settings', ['tableKey' => 'profiles'])
                <div class="overflow-x-auto">
                    <table class="data-table text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                @include('partials.sortable-table-heading', ['columnKey' => 'name', 'sortKey' => 'name', 'label' => 'Название', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'query', 'sortKey' => 'query', 'label' => 'Поисковый запрос', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'criteria', 'sortKey' => 'criteria', 'label' => 'Критерии', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'listings', 'sortKey' => 'listings', 'label' => 'Лотов', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'min_price', 'sortKey' => 'min_price', 'label' => 'Цена от (USD)', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'max_price', 'sortKey' => 'max_price', 'label' => 'Цена до (USD)', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'status', 'sortKey' => 'status', 'label' => 'Статус', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'schedule', 'sortKey' => 'schedule', 'label' => 'Расписание', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'last-search', 'sortKey' => 'last_search', 'label' => 'Последний поиск', 'sort' => $sort])
                                <th class="p-3" data-column-key="actions">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($profiles as $profile)
                                <tr>
                                    <td class="p-3 font-medium">{{ $profile->name }}</td>
                                    <td class="p-3">{{ $profile->query }}</td>
                                    <td class="p-3">
                                        <textarea
                                            readonly
                                            rows="5"
                                            aria-label="Критерии профиля {{ $profile->name }}"
                                            class="block w-full resize-none overflow-y-auto rounded border border-slate-300 bg-slate-50 p-2 text-xs leading-5 text-slate-700"
                                        >Состояние: {{ $profile->condition ?? 'любое' }}
Цена: {{ $profile->min_price ? '$'.number_format($profile->min_price) : 'без минимума' }}–{{ $profile->max_price ? '$'.number_format($profile->max_price) : 'без максимума' }}
Бренд: {{ collect($profile->brands ?: [$profile->brand])->filter()->join(', ') ?: 'любой' }} · CPU: {{ collect($profile->processors ?: [$profile->processor])->filter()->join(', ') ?: 'любой' }}
RAM: {{ collect($profile->ram_sizes ?: [$profile->ram_size ?? ($profile->min_ram_gb ? $profile->min_ram_gb.' GB' : null)])->filter()->join(', ') ?: 'любая' }} · Накопитель: {{ collect($profile->storage_capacities ?: [$profile->storage_capacity])->filter()->join(', ') ?: 'любой' }}
Экран: {{ collect($profile->screen_sizes ?: [$profile->screen_size])->filter()->join(', ') ?: 'любой' }} · GPU: {{ collect($profile->gpus ?: [$profile->gpu])->filter()->join(', ') ?: 'любая' }}
ОС: {{ $profile->operating_system ?? 'любая' }}</textarea>
                                    </td>
                                    <td class="p-3">
                                        <button type="button" data-profile-listings-trigger data-profile-url="{{ route('profiles.listings', $profile) }}" class="font-medium text-blue-700 hover:underline cursor-pointer">
                                            {{ $profile->listings_count }}
                                        </button>
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ null !== $profile->min_price ? '$'.number_format($profile->min_price) : '—' }}
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ null !== $profile->max_price ? '$'.number_format($profile->max_price) : '—' }}
                                    </td>
                                    <td class="p-3">
                                        <span class="{{ $profile->active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }} rounded-full px-2 py-1 text-xs">
                                            {{ $profile->active ? 'Активен' : 'Отключён' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ substr($profile->schedule_time, 0, 5) }},
                                        {{ $profile->schedule_interval_hours === 24 ? 'раз в сутки' : 'каждые '.$profile->schedule_interval_hours.' ч.' }}
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        @if ($profile->last_synced_at)
                                            <button type="button" data-activities-trigger data-activities-url="{{ route('profiles.recent-activities', $profile) }}" class="text-blue-700 hover:underline cursor-pointer">{{ $profile->last_synced_at->format('d.m.Y H:i') }}</button>
                                        @else
                                            Ещё не запускался
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <div class="flex w-full min-w-0 flex-col items-start gap-2">
                                            <a href="{{ route('profiles.edit', $profile) }}" class="font-medium text-blue-700 hover:underline">Редактировать</a>
                                            <form method="POST" action="{{ route('profiles.destroy', $profile) }}"
                                                data-confirm-dialog-form
                                                data-confirm-message="Удалить этот профиль и все связанные с ним лоты?"
                                                data-confirm-button="Удалить профиль">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-700 hover:underline">Удалить</button>
                                            </form>
                                            <button
                                                type="button"
                                                data-parse-button
                                                data-parse-url="{{ route('profiles.parse', $profile) }}"
                                                data-parse-profile-name="{{ $profile->name }}"
                                                class="max-w-full rounded bg-blue-600 px-3 py-2 text-left font-medium whitespace-normal text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-60"
                                            >
                                                Парсить
                                            </button>
                                            <button
                                                type="button"
                                                data-analyze-filters
                                                data-analyze-url="{{ route('profiles.analyze-filters', $profile) }}"
                                                data-analyze-profile-name="{{ $profile->name }}"
                                                class="max-w-full rounded border border-amber-300 px-3 py-2 text-left text-sm font-medium whitespace-normal text-amber-900 hover:bg-amber-50 disabled:cursor-wait disabled:opacity-60"
                                            >
                                                Фильтры
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" data-column-empty-state class="p-8 text-center text-slate-500">Профилей пока нет. Создайте профиль через меню «Добавить».</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </main>

        <dialog
            id="parse-progress-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="parse-progress-title"
            aria-describedby="parse-progress-status"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(34rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="p-6">
                <h2 id="parse-progress-title" class="text-xl font-semibold" data-parse-title>Парсинг</h2>
                <p class="mt-1 text-sm text-slate-500 hidden" data-parse-running-profile-name></p>
                <p class="mt-1 text-sm text-slate-500 hidden" data-parse-finished-profile-name></p>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Уникальных товаров обработано</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums" data-parse-processed>0</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Осталось обработать</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums" data-parse-remaining>Определяется</p>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                        <span>Прогресс</span>
                        <span class="font-semibold tabular-nums" data-parse-percent>0%</span>
                    </div>
                    <progress class="h-3 w-full accent-blue-600" max="100" value="0" data-parse-progress aria-label="Прогресс парсинга"></progress>
                </div>
                <p id="parse-progress-status" class="mt-3 text-sm text-slate-600" data-parse-status role="status" aria-live="polite">Подготовка к поиску…</p>
                <p class="mt-1 text-xs text-slate-500" data-parse-result></p>
                <div id="parse-saved-listings" class="mt-4 hidden" data-parse-saved-listings></div>
                <div class="mt-6 flex justify-end">
                    <button type="button" data-stop-parse class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">Остановить парсинг</button>
                    <button type="button" data-close-parse-dialog class="hidden rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Закрыть</button>
                </div>
            </div>
        </dialog>

        <dialog
            id="filter-analysis-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="filter-analysis-title"
            aria-describedby="filter-analysis-result"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(72rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="p-6">
                <h2 id="filter-analysis-title" class="text-xl font-semibold">Результат проверки фильтров</h2>
                <div id="filter-analysis-result" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm" data-filter-analysis-result role="status" aria-live="polite"></div>
                <div class="mt-5 flex justify-end">
                    <button type="button" data-close-filter-analysis-dialog class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Закрыть</button>
                </div>
            </div>
        </dialog>
        <dialog
            id="profile-listings-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="profile-listings-title"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(72rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="p-6">
                <h2 id="profile-listings-title" class="text-xl font-semibold">Товары профиля</h2>
                <div id="profile-listings-loading" class="mt-4 text-center text-slate-500">
                    <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="mt-2">Загрузка товаров…</p>
                </div>
                <div id="profile-listings-content" class="mt-4 hidden"></div>
                <div id="profile-listings-pagination" class="mt-4 flex justify-center gap-2 hidden"></div>
                <div class="mt-6 flex justify-end">
                    <button type="button" data-close-profile-listings-dialog class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Закрыть</button>
                </div>
            </div>
        </dialog>

        <dialog
            id="create-profile-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-profile-title"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(56rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="p-6">
                <h2 id="create-profile-title" class="text-2xl font-bold">Новый критерий поиска</h2>
                <p class="mt-1 text-sm text-slate-500">Списки характеристик загружаются из фильтров eBay для категории «Ноутбуки».</p>

                @if ($errors->any())
                    <div class="mt-4 rounded bg-red-100 p-3 text-red-800">
                        <p class="font-medium">Проверьте заполненные поля.</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profiles.store') }}" class="mt-4 space-y-5" id="create-profile-form">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="create_name" class="mb-1 block text-sm font-medium">Название профиля</label>
                            <input id="create_name" name="name" value="{{ old('name') }}" placeholder="Например, ThinkPad" maxlength="255" required class="w-full rounded border p-2">
                            @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_query" class="mb-1 block text-sm font-medium">Поисковый запрос eBay</label>
                            <input id="create_query" name="query" data-ebay-query value="{{ old('query', 'laptop') }}" placeholder="Например, laptop" maxlength="255" required class="w-full rounded border p-2">
                            @error('query')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_schedule_time" class="mb-1 block text-sm font-medium">Ежедневный запуск (время Бишкека)</label>
                            <input id="create_schedule_time" name="schedule_time" type="time" value="{{ old('schedule_time', '08:00') }}" required class="w-full rounded border p-2">
                            <p class="mt-1 text-xs text-slate-500">Первый запуск каждого дня (UTC+6).</p>
                            @error('schedule_time')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_schedule_interval_hours" class="mb-1 block text-sm font-medium">Интервал повторного запуска</label>
                            <select id="create_schedule_interval_hours" name="schedule_interval_hours" required class="w-full rounded border p-2">
                                @for ($hours = 1; $hours <= 24; $hours++)
                                    <option value="{{ $hours }}" @selected((int) old('schedule_interval_hours', 24) === $hours)>
                                        {{ $hours === 24 ? 'Раз в сутки' : "Каждые {$hours} ч." }}
                                    </option>
                                @endfor
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Повторять от времени начала; для 24 часов — один запуск в сутки.</p>
                            @error('schedule_interval_hours')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_condition" class="mb-1 block text-sm font-medium">Состояние товара</label>
                            <select id="create_condition" name="condition" class="w-full rounded border p-2">
                                <option value="" @selected(old('condition') === null)>Любое состояние</option>
                                <option value="USED" @selected(old('condition') === 'USED')>Б/у</option>
                                <option value="REFURBISHED" @selected(old('condition') === 'REFURBISHED')>Refurbished</option>
                                <option value="NEW" @selected(old('condition') === 'NEW')>Новое</option>
                            </select>
                            @error('condition')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_min_price" class="mb-1 block text-sm font-medium">Цена от (USD)</label>
                            <input id="create_min_price" name="min_price" type="number" min="0" step="1" value="{{ old('min_price') }}" placeholder="Без минимума" class="w-full rounded border p-2">
                            @error('min_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_max_price" class="mb-1 block text-sm font-medium">Цена до (USD)</label>
                            <input id="create_max_price" name="max_price" type="number" min="1" step="1" value="{{ old('max_price') }}" placeholder="Без максимума" class="w-full rounded border p-2">
                            @error('max_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="create_price_alert_threshold" class="mb-1 block text-sm font-medium">Целевая цена для уведомления (USD)</label>
                            <input id="create_price_alert_threshold" name="price_alert_threshold" type="number" min="0" step="0.01" value="{{ old('price_alert_threshold') }}" placeholder="Без целевой цены" class="w-full rounded border p-2">
                            <p class="mt-1 text-xs text-slate-500">Уведомим в Telegram, когда цена с доставкой снизится ниже этой суммы.</p>
                            @error('price_alert_threshold')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <fieldset class="space-y-3 rounded-lg border p-4">
                        <legend class="px-1 text-sm font-semibold">Уведомления Telegram</legend>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="notify_new_listings" value="0">
                            <input type="checkbox" name="notify_new_listings" value="1" @checked((bool) old('notify_new_listings', true)) class="rounded border">
                            Новые товары
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="notify_price_drops" value="0">
                            <input type="checkbox" name="notify_price_drops" value="1" @checked((bool) old('notify_price_drops', true)) class="rounded border">
                            Снижение цены
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="notify_search_errors" value="0">
                            <input type="checkbox" name="notify_search_errors" value="1" @checked((bool) old('notify_search_errors', true)) class="rounded border">
                            Ошибки поиска
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="notify_profile_matches" value="0">
                            <input type="checkbox" name="notify_profile_matches" value="1" @checked((bool) old('notify_profile_matches', true)) class="rounded border">
                            Лот найден в новом профиле
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="notify_shared_price_changes" value="0">
                            <input type="checkbox" name="notify_shared_price_changes" value="1" @checked((bool) old('notify_shared_price_changes', true)) class="rounded border">
                            Изменение цены в нескольких профилях
                        </label>
                        <div>
                            <label for="create_notification_frequency" class="mb-1 block text-sm font-medium">Частота уведомлений</label>
                            <select id="create_notification_frequency" name="notification_frequency" class="w-full rounded border p-2">
                                <option value="immediate" @selected(old('notification_frequency', 'immediate') === 'immediate')>Сразу при событии</option>
                                <option value="daily" @selected(old('notification_frequency', 'immediate') === 'daily')>Ежедневная сводка в {{ old('schedule_time', '08:00') }}</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Сводка отправляется во время ежедневного запуска профиля (время Бишкека).</p>
                            @error('notification_frequency')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </fieldset>

                    @include('profiles._aspect-filters', ['filterValues' => []])

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Сохранить профиль</button>
                        <button type="button" data-close-create-dialog class="text-sm text-slate-600 hover:underline">Отмена</button>
                    </div>
                </form>
            </div>
        </dialog>

        @include('partials.confirm-dialog')

        <dialog
            id="activities-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="activities-title"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(42rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="p-6">
                <h2 id="activities-title" class="text-xl font-semibold">История парсинга</h2>
                <div id="activities-list" class="mt-4" data-activities-list role="status" aria-live="polite"></div>
                <div class="mt-5 flex justify-end">
                    <button type="button" data-close-activities-dialog class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Закрыть</button>
                </div>
            </div>
        </dialog>

        <dialog
            id="analyzing-filters-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="analyzing-filters-title"
            class="fixed left-1/2 top-1/2 m-0 w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-900 shadow-xl backdrop:bg-slate-900/60"
        >
            <div class="flex flex-col items-center gap-4">
                <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                <div>
                    <p id="analyzing-filters-title" class="text-lg font-semibold">Идет проверка фильтров</p>
                    <p id="analyzing-filters-profile-name" class="mt-1 text-sm text-slate-500"></p>
                </div>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const dialog = document.querySelector('#parse-progress-dialog');
                const parseTitle = dialog.querySelector('[data-parse-title]');
                const parseProfileName = dialog.querySelector('[data-parse-profile-name]');
                const result = dialog.querySelector('[data-parse-result]');
                const progress = dialog.querySelector('[data-parse-progress]');
                const progressPercent = dialog.querySelector('[data-parse-percent]');
                const processedCount = dialog.querySelector('[data-parse-processed]');
                const remainingCount = dialog.querySelector('[data-parse-remaining]');
                const parseStatus = dialog.querySelector('[data-parse-status]');
                const savedListingsContainer = dialog.querySelector('[data-parse-saved-listings]');
                const runningProfileNameEl = dialog.querySelector('[data-parse-running-profile-name]');
                const finishedProfileNameEl = dialog.querySelector('[data-parse-finished-profile-name]');
                const stopParseButton = dialog.querySelector('[data-stop-parse]');
                const closeParseButton = dialog.querySelector('[data-close-parse-dialog]');
                const filterAnalysisDialog = document.querySelector('#filter-analysis-dialog');
                const filterAnalysisResult = filterAnalysisDialog.querySelector('[data-filter-analysis-result]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const createProfileDialog = document.querySelector('#create-profile-dialog');
                let activeAnalysisButton = null;
                let activeParseButton = null;
                let parseDialogTrigger = null;
                let stopRequested = false;

                // Create profile dialog
                document.querySelector('[data-open-create-dialog]').addEventListener('click', () => createProfileDialog.showModal());
                createProfileDialog.querySelector('[data-close-create-dialog]').addEventListener('click', () => createProfileDialog.close());
                createProfileDialog.addEventListener('click', (event) => {
                    if (event.target === createProfileDialog) {
                        createProfileDialog.close();
                    }
                });

                closeParseButton.addEventListener('click', () => {
                    dialog.close();
                    window.location.reload();
                });
                let parseAbortController = null;
                stopParseButton.addEventListener('click', () => {
                    if (!activeParseButton || stopRequested) {
                        return;
                    }

                    stopRequested = true;
                    stopParseButton.disabled = true;
                    stopParseButton.textContent = 'Останавливаем…';
                    parseStatus.textContent = 'Запрос прерван; парсинг остановлен.';

                    // Abort the current fetch request immediately
                    if (parseAbortController) {
                        parseAbortController.abort();
                    }
                });
                dialog.addEventListener('cancel', (event) => {
                    if (activeParseButton) {
                        event.preventDefault();
                    }
                });
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog && activeParseButton) {
                        event.preventDefault();
                    }
                });
                dialog.addEventListener('close', () => {
                    parseDialogTrigger?.focus();
                    parseDialogTrigger = null;
                });

                filterAnalysisDialog.querySelector('[data-close-filter-analysis-dialog]').addEventListener('click', () => filterAnalysisDialog.close());
                filterAnalysisDialog.addEventListener('click', (event) => {
                    if (event.target === filterAnalysisDialog) {
                        filterAnalysisDialog.close();
                    }
                });
                filterAnalysisDialog.addEventListener('close', () => activeAnalysisButton?.focus());

                const analyzingFiltersDialog = document.querySelector('#analyzing-filters-dialog');
                const analyzingFiltersProfileName = analyzingFiltersDialog.querySelector('#analyzing-filters-profile-name');

                function setDialogWide(wide) {
                    dialog.style.width = wide
                        ? 'min(72rem, calc(100% - 2rem))'
                        : 'min(34rem, calc(100% - 2rem))';
                }

                function renderSavedListings(listings, showAll = false) {
                    if (listings.length === 0) {
                        savedListingsContainer.classList.add('hidden');
                        savedListingsContainer.innerHTML = '';
                        return;
                    }

                    setDialogWide(true);
                    savedListingsContainer.classList.remove('hidden');
                    const displayListings = showAll ? listings : listings.slice(0, 10);
                    const tbodyRows = displayListings.map(listing => `
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2 max-w-md truncate" title="${escapeHtml(listing.title)}">${escapeHtml(listing.title)}</td>
                            <td class="px-4 py-2 whitespace-nowrap tabular-nums">${listing.price.toFixed(2)} ${listing.currency}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="${listing.location_url}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium no-underline hover:opacity-80 ${listing.location === 'Исключённый товар' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}">${escapeHtml(listing.location)}</a>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="${listing.item_url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Открыть</a>
                            </td>
                        </tr>
                    `).join('');
                    const table = document.createElement('div');
                    table.className = 'overflow-x-auto rounded-lg border border-slate-200';
                    const footerHtml = (!showAll && listings.length > 10) ? `
                        <div class="px-4 py-2 border-t border-slate-200 text-center">
                            <button type="button" data-show-all-listings class="text-sm text-blue-600 hover:underline">Показать все (${listings.length})</button>
                        </div>
                    ` : '';
                    table.innerHTML = `
                        <h3 class="px-4 py-3 font-medium bg-slate-50 border-b border-slate-200">Сохранённые товары (${listings.length})</h3>
                        <div class="max-h-[30rem] overflow-y-auto">
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-left text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-medium">Название товара</th>
                                        <th class="px-4 py-2 font-medium w-32">Цена</th>
                                        <th class="px-4 py-2 font-medium w-36">Расположение</th>
                                        <th class="px-4 py-2 font-medium w-28">Ссылка</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    ${tbodyRows}
                                </tbody>
                            </table>
                        </div>
                        ${footerHtml}
                    `;
                    savedListingsContainer.innerHTML = '';
                    savedListingsContainer.appendChild(table);

                    const showAllBtn = savedListingsContainer.querySelector('[data-show-all-listings]');
                    if (showAllBtn) {
                        showAllBtn.addEventListener('click', () => renderSavedListings(listings, true));
                    }
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                for (const button of document.querySelectorAll('[data-analyze-filters]')) {
                    button.addEventListener('click', async () => {
                        activeAnalysisButton = button;
                        button.disabled = true;

                        analyzingFiltersProfileName.textContent = button.dataset.analyzeProfileName;
                        analyzingFiltersDialog.showModal();

                        try {
                            const response = await fetch(button.dataset.analyzeUrl, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                credentials: 'same-origin',
                            });
                            const analysis = await response.json();

                            if (!response.ok) {
                                throw new Error(analysis.message || 'Не удалось проверить фильтры.');
                            }

                            filterAnalysisResult.className = 'mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm';
                            const summary = document.createElement('p');
                            summary.className = 'font-medium';
                            summary.textContent = `Базовая выдача: ${analysis.baseline_count}. С текущими фильтрами: ${analysis.filtered_count}.`;

                            const details = document.createElement('ul');
                            details.className = 'mt-2 space-y-1';

                            if (analysis.filter_impacts.length === 0) {
                                const item = document.createElement('li');
                                item.textContent = 'Дополнительные фильтры не заданы.';
                                details.append(item);
                            }

                            for (const impact of analysis.filter_impacts) {
                                const item = document.createElement('li');
                                const remaining = impact.remaining_percent === null
                                    ? 'базовая выдача пуста'
                                    : `остаётся ${impact.remaining_percent}%`;
                                item.textContent = `${impact.label}: ${impact.value} — ${impact.result_count} результатов (${remaining}, −${impact.reduction}).`;
                                details.append(item);
                            }

                            filterAnalysisResult.replaceChildren(summary, details);

                            if (analysis.existing_count !== undefined || analysis.new_count !== undefined) {
                                const existingCount = analysis.existing_count ?? 0;
                                const newCount = analysis.new_count ?? 0;
                                const excludedCount = analysis.excluded_count ?? 0;
                                const filteredCount = analysis.filtered_count ?? 0;
                                if (filteredCount > 0) {
                                    const databaseInfo = document.createElement('div');
                                    databaseInfo.className = 'mt-3 rounded-lg border border-gray-200 bg-slate-50 p-3';
                                    const profileName = document.createElement('p');
                                    profileName.className = 'font-medium text-slate-900';
                                    profileName.textContent = button.dataset.analyzeProfileName;
                                    const databaseTitle = document.createElement('p');
                                    databaseTitle.className = 'mt-1 font-medium text-slate-900';
                                    databaseTitle.textContent = 'Общее количество: ' + filteredCount.toLocaleString('ru-RU') + ' товаров';
                                    const databaseDetails = document.createElement('ul');
                                    databaseDetails.className = 'mt-1 space-y-0.5 text-slate-700';
                                    const existingItem = document.createElement('li');
                                    existingItem.textContent = '✓ Уже в базе: ' + existingCount.toLocaleString('ru-RU') + ' товаров';
                                    const newItem = document.createElement('li');
                                    newItem.textContent = '+ Будет добавлено: ~' + newCount + ' новых товаров (по выборке)';
                                    databaseDetails.append(existingItem, newItem);
                                    if (excludedCount > 0) {
                                        const excludedItem = document.createElement('li');
                                        excludedItem.textContent = '⚠ Будет исключено: ~' + excludedCount + ' товаров (по словам исключения)';
                                        databaseDetails.append(excludedItem);
                                    }
                                    databaseInfo.append(profileName, databaseTitle, databaseDetails);
                                    filterAnalysisResult.append(databaseInfo);
                                }
                            }

                            // Render sample listings table if available
                            if (analysis.sample_listings && analysis.sample_listings.length > 0) {
                                const sampleListings = analysis.sample_listings;
                                const tableContainer = document.createElement('div');
                                tableContainer.className = 'mt-3 overflow-x-auto rounded-lg border border-slate-200';
                                const tableHeader = document.createElement('div');
                                tableHeader.className = 'px-4 py-3 font-medium bg-slate-50 border-b border-slate-200';
                                tableHeader.textContent = 'Образцы товаров (' + sampleListings.length + ')';
                                const tableScroll = document.createElement('div');
                                tableScroll.className = 'max-h-[24rem] overflow-y-auto';
                                const table = document.createElement('table');
                                table.className = 'min-w-full text-sm';
                                const thead = document.createElement('thead');
                                thead.className = 'sticky top-0 bg-slate-50 text-left text-slate-500';
                                thead.innerHTML = `
                                    <tr>
                                        <th class="px-4 py-2 font-medium">Название товара</th>
                                        <th class="px-4 py-2 font-medium w-32">Цена</th>
                                        <th class="px-4 py-2 font-medium w-36">Расположение</th>
                                        <th class="px-4 py-2 font-medium w-28">Ссылка</th>
                                    </tr>
                                `;
                                const tbody = document.createElement('tbody');
                                tbody.className = 'divide-y divide-slate-200';
                                sampleListings.forEach(listing => {
                                    const tr = document.createElement('tr');
                                    tr.className = 'hover:bg-slate-50';
                                    const locationClass = listing.location === 'Исключённый товар'
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-green-100 text-green-800';
                                    tr.innerHTML = `
                                        <td class="px-4 py-2 max-w-md truncate" title="${escapeHtml(listing.title)}">${escapeHtml(listing.title)}</td>
                                        <td class="px-4 py-2 whitespace-nowrap tabular-nums">${listing.price.toFixed(2)} ${listing.currency}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <a href="${listing.location_url}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium no-underline hover:opacity-80 ${locationClass}">${escapeHtml(listing.location)}</a>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <a href="${listing.item_url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Открыть</a>
                                        </td>
                                    `;
                                    tbody.appendChild(tr);
                                });
                                table.appendChild(thead);
                                table.appendChild(tbody);
                                tableScroll.appendChild(table);
                                tableContainer.appendChild(tableHeader);
                                tableContainer.appendChild(tableScroll);
                                filterAnalysisResult.append(tableContainer);
                            }

                            if (analysis.recommendation) {
                                const recommendation = document.createElement('p');
                                recommendation.className = 'mt-3 rounded bg-amber-100 p-2 font-medium text-amber-950';
                                recommendation.textContent = analysis.recommendation;
                                filterAnalysisResult.append(recommendation);
                            }
                            analyzingFiltersDialog.close();
                            filterAnalysisDialog.showModal();
                        } catch (error) {
                            analyzingFiltersDialog.close();
                            filterAnalysisResult.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800';
                            filterAnalysisResult.textContent = error instanceof Error
                                ? error.message
                                : 'Не удалось проверить фильтры. Попробуйте ещё раз.';
                            filterAnalysisDialog.showModal();
                        } finally {
                            analyzingFiltersDialog.close();
                            button.disabled = false;
                            button.textContent = 'Фильтры';
                        }
                    });
                }

                for (const button of document.querySelectorAll('[data-parse-button]')) {
                    button.addEventListener('click', async () => {
                        if (activeParseButton) {
                            return;
                        }

                        activeParseButton = button;
                        parseDialogTrigger = button;
                        stopRequested = false;
                        let segment = {
                            offset: 0,
                            price_min_cents: null,
                            price_max_cents: null,
                        };
                        const pendingSegments = [];
                        let found = null;
                        let processed = 0;
                        const processedItemIds = new Set();
                        let imported = 0;
                        let unprocessed = 0;
                        const allSavedListings = [];
                        const runId = crypto.randomUUID();

                        button.disabled = true;
                        button.textContent = 'Парсинг…';
                        progress.value = 0;
                        progressPercent.textContent = '0%';
                        processedCount.textContent = '0';
                        remainingCount.textContent = 'Определяется';
                        const parseProfileName = button.dataset.parseProfileName;
                        parseTitle.textContent = 'Парсинг';
                        runningProfileNameEl.textContent = parseProfileName;
                        runningProfileNameEl.classList.remove('hidden');
                        finishedProfileNameEl.classList.add('hidden');
                        parseStatus.textContent = 'Подключаемся к eBay…';
                        parseStatus.classList.remove('text-red-700');
                        result.textContent = '';
                        savedListingsContainer.classList.add('hidden');
                        savedListingsContainer.innerHTML = '';
                        setDialogWide(false);
                        stopParseButton.disabled = false;
                        stopParseButton.textContent = 'Остановить парсинг';
                        stopParseButton.classList.remove('hidden');
                        closeParseButton.classList.add('hidden');
                        dialog.showModal();

                        try {
                            let done = false;

                            while (!done) {
                                parseAbortController = new AbortController();
                                const response = await fetch(button.dataset.parseUrl, {
                                    method: 'POST',
                                    headers: {
                                        Accept: 'application/json',
                                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: new URLSearchParams({
                                        offset: String(segment.offset),
                                        run_id: runId,
                                        imported_total: String(imported),
                                        total_found: found === null ? '' : String(found),
                                        price_min_cents: segment.price_min_cents === null
                                            ? ''
                                            : String(segment.price_min_cents),
                                        price_max_cents: segment.price_max_cents === null
                                            ? ''
                                            : String(segment.price_max_cents),
                                        has_pending_partitions: pendingSegments.length > 0 ? '1' : '0',
                                    }),
                                    signal: parseAbortController.signal,
                                });
                                parseAbortController = null;
                                const page = await response.json();

                                if (!response.ok) {
                                    throw new Error(page.message || 'Не удалось выполнить поиск eBay.');
                                }

                                found ??= page.found;
                                for (const itemId of page.page_item_ids) {
                                    processedItemIds.add(itemId);
                                }
                                processed = processedItemIds.size;
                                imported += page.imported;
                                unprocessed += page.unprocessed_count;
                                if (Array.isArray(page.saved_listings)) {
                                    allSavedListings.push(...page.saved_listings);
                                }
                                pendingSegments.push(...page.next_partitions);

                                const hasMoreWork = !page.done || pendingSegments.length > 0;
                                const progressValue = hasMoreWork
                                    ? found === 0
                                        ? 0
                                        : Math.min(99, Math.floor(processed / found * 100))
                                    : 100;
                                progress.value = progressValue;
                                progressPercent.textContent = `${progressValue}%`;
                                processedCount.textContent = String(processed);
                                remainingCount.textContent = hasMoreWork && processed >= found
                                    ? 'Уточняется'
                                    : String(Math.max((found ?? 0) - processed, 0));
                                parseStatus.textContent = `eBay нашёл ${found} товаров. Сохранено или обновлено: ${imported}.${page.next_partitions.length > 0 ? ' Продолжаем поиск в следующих ценовых диапазонах.' : ''}`;

                                if (!page.done && page.next_offset <= segment.offset) {
                                    throw new Error('eBay не вернул следующую страницу результатов.');
                                }

                                if (stopRequested && (!page.done || pendingSegments.length > 0)) {
                                    parseTitle.textContent = 'Парсинг остановлен';
                                    runningProfileNameEl.classList.add('hidden');
                                    finishedProfileNameEl.textContent = parseProfileName;
                                    finishedProfileNameEl.classList.remove('hidden');
                                    parseStatus.textContent = `Остановлено после текущей страницы. Уникальных товаров обработано: ${processed}; при запуске eBay сообщил: ${found}.`;
                                    result.textContent = `Сохранено или обновлено товаров: ${imported}.`;
                                    renderSavedListings(allSavedListings);
                                    break;
                                }

                                if (page.done) {
                                    if (pendingSegments.length === 0) {
                                        done = true;
                                    } else {
                                        segment = pendingSegments.shift();
                                    }
                                } else {
                                    segment.offset = page.next_offset;
                                }
                            }

                            if (done) {
                                const unresolvedCount = Math.max(unprocessed, (found ?? 0) - processed);
                                parseTitle.textContent = unresolvedCount > 0
                                    ? 'Парсинг завершён не полностью'
                                    : 'Парсинг завершён';
                                runningProfileNameEl.classList.add('hidden');
                                finishedProfileNameEl.textContent = parseProfileName;
                                finishedProfileNameEl.classList.remove('hidden');
                                parseStatus.textContent = unresolvedCount > 0
                                    ? `Уникальных товаров обработано: ${processed}. При запуске eBay сообщил: ${found}.`
                                    : `Обработка завершена. Уникальных товаров обработано: ${processed}. При запуске eBay сообщил: ${found}.`;
                                const remainingMessage = unprocessed > 0
                                    ? ` Не удалось обработать ${unprocessed} товаров: лимит eBay не позволяет продолжить поиск внутри диапазона одинаковой цены.`
                                    : unresolvedCount > 0
                                        ? ` Не удалось обработать ещё ${unresolvedCount} результатов; возможно, выдача eBay изменилась во время поиска.`
                                        : '';
                                result.textContent = `Сохранено или обновлено товаров: ${imported}.${remainingMessage}`;
                                renderSavedListings(allSavedListings);
                            }
                        } catch (error) {
                            if (error.name === 'AbortError') {
                                parseTitle.textContent = 'Парсинг остановлен';
                                runningProfileNameEl.classList.add('hidden');
                                finishedProfileNameEl.textContent = parseProfileName;
                                finishedProfileNameEl.classList.remove('hidden');
                                parseStatus.textContent = `Остановлено пользователем. Уникальных товаров обработано: ${processed}; при запуске eBay сообщил: ${found ?? 0}.`;
                                parseStatus.classList.remove('text-red-700');
                                result.textContent = `Сохранено или обновлено товаров: ${imported}.`;
                                renderSavedListings(allSavedListings);
                            } else {
                                parseTitle.textContent = 'Ошибка парсинга';
                                runningProfileNameEl.classList.add('hidden');
                                parseStatus.textContent = error instanceof Error ? error.message : 'Не удалось выполнить поиск eBay.';
                                parseStatus.classList.add('text-red-700');
                            }
                        } finally {
                            button.disabled = false;
                            button.textContent = 'Парсить';
                            activeParseButton = null;
                            stopParseButton.classList.add('hidden');
                            closeParseButton.classList.remove('hidden');
                        }
                    });
                }

                const activitiesDialog = document.querySelector('#activities-dialog');
                const activitiesList = activitiesDialog.querySelector('[data-activities-list]');
                activitiesDialog.querySelector('[data-close-activities-dialog]').addEventListener('click', () => activitiesDialog.close());
                activitiesDialog.addEventListener('click', (event) => {
                    if (event.target === activitiesDialog) {
                        activitiesDialog.close();
                    }
                });

                for (const trigger of document.querySelectorAll('[data-activities-trigger]')) {
                    trigger.addEventListener('click', async () => {
                        activitiesList.innerHTML = '<p class="text-sm text-slate-500">Загрузка…</p>';
                        activitiesDialog.showModal();

                        try {
                            const response = await fetch(trigger.dataset.activitiesUrl, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            const data = await response.json();

                            if (!response.ok || !data.activities || data.activities.length === 0) {
                                activitiesList.innerHTML = '<p class="text-sm text-slate-500">История парсинга пуста.</p>';
                                return;
                            }

                            const table = document.createElement('div');
                            table.className = 'overflow-x-auto';
                            table.innerHTML = `
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-left text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Дата</th>
                                            <th class="px-3 py-2">Статус</th>
                                            <th class="px-3 py-2">Найдено</th>
                                            <th class="px-3 py-2">Новых</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        ${data.activities.map(a => `
                                            <tr title="${a.description ?? ''}">
                                                <td class="px-3 py-2 whitespace-nowrap">${a.date}</td>
                                                <td class="px-3 py-2">
                                                    <span class="${a.action === 'Ошибка' ? 'bg-red-100 text-red-800' : a.action === 'Завершён' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'} rounded-full px-2 py-0.5 text-xs">${a.action}</span>
                                                </td>
                                                <td class="px-3 py-2">${a.found !== null && a.found !== undefined ? a.found : '—'}</td>
                                                <td class="px-3 py-2">${a.new_listings !== null && a.new_listings !== undefined ? a.new_listings : '—'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                            activitiesList.innerHTML = '';
                            activitiesList.appendChild(table);
                        } catch (error) {
                            activitiesList.innerHTML = '<p class="text-sm text-red-600">Не удалось загрузить историю.</p>';
                        }
                    });
                }

                // Profile listings dialog
                const profileListingsDialog = document.querySelector('#profile-listings-dialog');
                const profileListingsLoading = document.querySelector('#profile-listings-loading');
                const profileListingsContent = document.querySelector('#profile-listings-content');
                const profileListingsPagination = document.querySelector('#profile-listings-pagination');
                const profileListingsTitle = document.querySelector('#profile-listings-title');
                let currentProfileListingsPage = 1;
                let currentProfileListingsUrl = '';

                profileListingsDialog.querySelector('[data-close-profile-listings-dialog]').addEventListener('click', () => {
                    profileListingsDialog.close();
                });

                profileListingsDialog.addEventListener('click', (event) => {
                    if (event.target === profileListingsDialog) {
                        profileListingsDialog.close();
                    }
                });

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                async function loadProfileListings(page = 1) {
                    currentProfileListingsPage = page;
                    const url = currentProfileListingsUrl + (page > 1 ? `?page=${page}` : '');

                    profileListingsLoading.classList.remove('hidden');
                    profileListingsContent.classList.add('hidden');
                    profileListingsPagination.classList.add('hidden');

                    try {
                        const response = await fetch(url, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();

                        if (!response.ok || !data.listings || data.listings.length === 0) {
                            profileListingsLoading.classList.add('hidden');
                            profileListingsContent.innerHTML = '<p class="text-center text-slate-500">Товаров не найдено.</p>';
                            profileListingsContent.classList.remove('hidden');
                            return;
                        }

                        const tableContainer = document.createElement('div');
                        tableContainer.className = 'overflow-x-auto rounded-lg border border-slate-200';
                        const tableScroll = document.createElement('div');
                        tableScroll.className = 'max-h-[24rem] overflow-y-auto';
                        const table = document.createElement('table');
                        table.className = 'min-w-full text-sm';

                        const thead = document.createElement('thead');
                        thead.className = 'sticky top-0 bg-slate-50 text-left text-slate-500';
                        thead.innerHTML = `
                            <tr>
                                <th class="px-4 py-2 font-medium">Название товара</th>
                                <th class="px-4 py-2 font-medium w-32">Цена</th>
                                <th class="px-4 py-2 font-medium w-36">Расположение</th>
                                <th class="px-4 py-2 font-medium w-28">Ссылка</th>
                            </tr>
                        `;

                        const tbody = document.createElement('tbody');
                        tbody.className = 'divide-y divide-slate-200';

                        data.listings.forEach(listing => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-slate-50';
                            const locationClass = listing.excluded
                                ? 'bg-yellow-100 text-yellow-800'
                                : 'bg-green-100 text-green-800';
                            const locationLabel = listing.excluded ? 'Исключённый товар' : 'Список товаров';
                            const locationUrl = listing.excluded
                                ? `/listings/excluded?search=${encodeURIComponent(listing.title)}&search_in=title`
                                : `/listings?search=${encodeURIComponent(listing.title)}&search_in=title`;
                            const price = listing.price ?? listing.total_price ?? 0;
                            const currency = listing.currency ?? 'USD';

                            tr.innerHTML = `
                                <td class="px-4 py-2 max-w-md truncate" title="${escapeHtml(listing.title)}">${escapeHtml(listing.title)}</td>
                                <td class="px-4 py-2 whitespace-nowrap tabular-nums">${parseFloat(price).toFixed(2)} ${escapeHtml(currency)}</td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <a href="${locationUrl}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium no-underline hover:opacity-80 ${locationClass}">${escapeHtml(locationLabel)}</a>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <a href="${escapeHtml(listing.item_url)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Открыть</a>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });

                        table.appendChild(thead);
                        table.appendChild(tbody);
                        tableScroll.appendChild(table);
                        tableContainer.appendChild(tableScroll);

                        profileListingsContent.innerHTML = '';
                        profileListingsContent.appendChild(tableContainer);
                        profileListingsContent.classList.remove('hidden');
                        profileListingsLoading.classList.add('hidden');

                        // Pagination
                        if (data.last_page > 1) {
                            profileListingsPagination.innerHTML = '';
                            profileListingsPagination.classList.remove('hidden');

                            if (data.current_page > 1) {
                                const prevBtn = document.createElement('button');
                                prevBtn.textContent = '← Назад';
                                prevBtn.className = 'rounded border border-slate-300 bg-white px-3 py-1 text-sm hover:bg-slate-50';
                                prevBtn.addEventListener('click', () => loadProfileListings(data.current_page - 1));
                                profileListingsPagination.appendChild(prevBtn);
                            }

                            const pageInfo = document.createElement('span');
                            pageInfo.className = 'px-3 py-1 text-sm text-slate-600';
                            pageInfo.textContent = `Страница ${data.current_page} из ${data.last_page} (всего: ${data.total})`;
                            profileListingsPagination.appendChild(pageInfo);

                            if (data.current_page < data.last_page) {
                                const nextBtn = document.createElement('button');
                                nextBtn.textContent = 'Далее →';
                                nextBtn.className = 'rounded border border-slate-300 bg-white px-3 py-1 text-sm hover:bg-slate-50';
                                nextBtn.addEventListener('click', () => loadProfileListings(data.current_page + 1));
                                profileListingsPagination.appendChild(nextBtn);
                            }
                        } else {
                            profileListingsPagination.classList.add('hidden');
                        }

                    } catch (error) {
                        profileListingsLoading.classList.add('hidden');
                        profileListingsContent.innerHTML = '<p class="text-center text-red-600">Не удалось загрузить товары.</p>';
                        profileListingsContent.classList.remove('hidden');
                    }
                }

                for (const trigger of document.querySelectorAll('[data-profile-listings-trigger]')) {
                    trigger.addEventListener('click', () => {
                        currentProfileListingsUrl = trigger.dataset.profileUrl;
                        currentProfileListingsPage = 1;
                        profileListingsLoading.classList.remove('hidden');
                        profileListingsContent.classList.add('hidden');
                        profileListingsPagination.classList.add('hidden');
                        profileListingsDialog.showModal();
                        loadProfileListings(1);
                    });
                }
            });

        /* Filter profiles table by search (name, min price, max price) */
        {
            const searchInput = document.querySelector('[data-profiles-search-input]');
            if (searchInput) {
                const table = searchInput.closest('section')?.querySelector('table.data-table');
                const tbody = table?.querySelector('tbody');

                searchInput.addEventListener('input', () => {
                    if (!tbody) return;
                    const query = searchInput.value.toLowerCase().trim();

                    tbody.querySelectorAll('tr').forEach(row => {
                        const cells = row.querySelectorAll('td');
                        if (cells.length < 4) return;

                        // Gather text from name (1st), min price (4th), and max price (5th) columns
                        const searchText = Array.from(cells)
                            .filter((_, i) => i === 0 || i === 3 || i === 4)
                            .map(cell => cell.textContent.toLowerCase())
                            .join(' ');

                        row.style.display = (!query || searchText.includes(query)) ? '' : 'none';
                    });
                });
            }
        }
        </script>
    </body>
</html>
