<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Новый критерий поиска — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')
            <div class="min-w-0">
            @if ($errors->any())
                <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                    <p class="font-medium">Проверьте заполненные поля.</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profiles.store') }}" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
                @csrf

                <header class="border-b border-slate-200 pb-4">
                    <h1 class="text-2xl font-bold">Новый критерий поиска</h1>
                    <p class="text-sm text-slate-500">Списки характеристик загружаются из фильтров eBay для категории «Ноутбуки».</p>
                </header>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium">Название профиля</label>
                        <input id="name" name="name" value="{{ old('name') }}" placeholder="Например, ThinkPad" maxlength="255" required class="w-full rounded border p-2">
                        @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="query" class="mb-1 block text-sm font-medium">Поисковый запрос eBay</label>
                        <input id="query" name="query" data-ebay-query value="{{ old('query', 'laptop') }}" placeholder="Например, laptop" maxlength="255" required class="w-full rounded border p-2">
                        @error('query')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="schedule_time" class="mb-1 block text-sm font-medium">Ежедневный запуск (время Бишкека)</label>
                        <input id="schedule_time" name="schedule_time" type="time" value="{{ old('schedule_time', '08:00') }}" required class="w-full rounded border p-2">
                        <p class="mt-1 text-xs text-slate-500">Первый запуск каждого дня (UTC+6).</p>
                        @error('schedule_time')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="schedule_interval_hours" class="mb-1 block text-sm font-medium">Интервал повторного запуска</label>
                        <select id="schedule_interval_hours" name="schedule_interval_hours" required class="w-full rounded border p-2">
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
                        <label for="condition" class="mb-1 block text-sm font-medium">Состояние товара</label>
                        <select id="condition" name="condition" class="w-full rounded border p-2">
                            <option value="" @selected(old('condition') === null)>Любое состояние</option>
                            <option value="USED" @selected(old('condition') === 'USED')>Б/у</option>
                            <option value="REFURBISHED" @selected(old('condition') === 'REFURBISHED')>Refurbished</option>
                            <option value="NEW" @selected(old('condition') === 'NEW')>Новое</option>
                        </select>
                        @error('condition')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="min_price" class="mb-1 block text-sm font-medium">Цена от (USD)</label>
                        <input id="min_price" name="min_price" type="number" min="0" step="1" value="{{ old('min_price') }}" placeholder="Без минимума" class="w-full rounded border p-2">
                        @error('min_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="max_price" class="mb-1 block text-sm font-medium">Цена до (USD)</label>
                        <input id="max_price" name="max_price" type="number" min="1" step="1" value="{{ old('max_price') }}" placeholder="Без максимума" class="w-full rounded border p-2">
                        @error('max_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="price_alert_threshold" class="mb-1 block text-sm font-medium">Целевая цена для уведомления (USD)</label>
                        <input id="price_alert_threshold" name="price_alert_threshold" type="number" min="0" step="0.01" value="{{ old('price_alert_threshold') }}" placeholder="Без целевой цены" class="w-full rounded border p-2">
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
                        <label for="notification_frequency" class="mb-1 block text-sm font-medium">Частота уведомлений</label>
                        <select id="notification_frequency" name="notification_frequency" class="w-full rounded border p-2">
                            <option value="immediate" @selected(old('notification_frequency', 'immediate') === 'immediate')>Сразу при событии</option>
                            <option value="daily" @selected(old('notification_frequency', 'immediate') === 'daily')>Ежедневная сводка в {{ old('schedule_time', '08:00') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Сводка отправляется во время ежедневного запуска профиля (время Бишкека).</p>
                        @error('notification_frequency')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                @include('profiles._aspect-filters', ['filterValues' => []])

                <div class="flex items-center gap-3">
                    <button class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Сохранить профиль</button>
                    <a href="{{ route('profiles.index') }}" class="text-sm text-slate-600 hover:underline">Отмена</a>
                </div>
            </form>
            </div>
        </main>
    </body>
</html>
