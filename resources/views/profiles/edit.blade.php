<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Редактирование профиля — eBay Finder</title>
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

            <form method="POST" action="{{ route('profiles.update', $profile) }}" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')

                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <h1 class="text-2xl font-bold">Редактирование профиля</h1>
                        <p class="text-sm text-slate-500">{{ $profile->name }}</p>
                    </div>
                    <a href="{{ route('profiles.index') }}" class="rounded border px-4 py-2 text-sm font-medium hover:bg-slate-50">К профилям</a>
                </header>

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">Название профиля</label>
                    <input id="name" name="name" value="{{ old('name', $profile->name) }}" maxlength="255" required class="w-full rounded border p-2">
                    @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="query" class="mb-1 block text-sm font-medium">Поисковый запрос eBay</label>
                    <input id="query" name="query" data-ebay-query value="{{ old('query', $profile->query) }}" maxlength="255" required class="w-full rounded border p-2">
                    @error('query')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="schedule_time" class="mb-1 block text-sm font-medium">Ежедневный запуск (время Бишкека)</label>
                    <input id="schedule_time" name="schedule_time" type="time" value="{{ old('schedule_time', substr($profile->schedule_time, 0, 5)) }}" required class="w-full rounded border p-2">
                    <p class="mt-1 text-xs text-slate-500">Время первого запуска каждого дня (UTC+6).</p>
                    @error('schedule_time')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="schedule_interval_hours" class="mb-1 block text-sm font-medium">Интервал повторного запуска</label>
                    <select id="schedule_interval_hours" name="schedule_interval_hours" required class="w-full rounded border p-2">
                        @for ($hours = 1; $hours <= 24; $hours++)
                            <option value="{{ $hours }}" @selected((int) old('schedule_interval_hours', $profile->schedule_interval_hours) === $hours)>
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
                        <option value="" @selected(old('condition', $profile->condition) === null)>Любое состояние</option>
                        <option value="USED" @selected(old('condition', $profile->condition) === 'USED')>Б/у</option>
                        <option value="REFURBISHED" @selected(old('condition', $profile->condition) === 'REFURBISHED')>Refurbished</option>
                        <option value="NEW" @selected(old('condition', $profile->condition) === 'NEW')>Новое</option>
                    </select>
                    @error('condition')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="min_price" class="mb-1 block text-sm font-medium">Цена от (USD)</label>
                        <input id="min_price" name="min_price" type="number" min="0" step="1" value="{{ old('min_price', $profile->min_price) }}" class="w-full rounded border p-2">
                        @error('min_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="max_price" class="mb-1 block text-sm font-medium">Цена до (USD)</label>
                        <input id="max_price" name="max_price" type="number" min="1" step="1" value="{{ old('max_price', $profile->max_price) }}" class="w-full rounded border p-2">
                        @error('max_price')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="price_alert_threshold" class="mb-1 block text-sm font-medium">Целевая цена для уведомления (USD)</label>
                        <input id="price_alert_threshold" name="price_alert_threshold" type="number" min="0" step="0.01" value="{{ old('price_alert_threshold', $profile->price_alert_threshold) }}" placeholder="Без целевой цены" class="w-full rounded border p-2">
                        <p class="mt-1 text-xs text-slate-500">Уведомим в Telegram, когда цена с доставкой снизится ниже этой суммы.</p>
                        @error('price_alert_threshold')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <fieldset class="space-y-3 rounded-lg border p-4">
                    <legend class="px-1 text-sm font-semibold">Уведомления Telegram</legend>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="notify_new_listings" value="0">
                        <input type="checkbox" name="notify_new_listings" value="1" @checked((bool) old('notify_new_listings', $profile->notify_new_listings)) class="rounded border">
                        Новые товары
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="notify_price_drops" value="0">
                        <input type="checkbox" name="notify_price_drops" value="1" @checked((bool) old('notify_price_drops', $profile->notify_price_drops)) class="rounded border">
                        Снижение цены
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="notify_search_errors" value="0">
                        <input type="checkbox" name="notify_search_errors" value="1" @checked((bool) old('notify_search_errors', $profile->notify_search_errors)) class="rounded border">
                        Ошибки поиска
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="notify_profile_matches" value="0">
                        <input type="checkbox" name="notify_profile_matches" value="1" @checked((bool) old('notify_profile_matches', $profile->notify_profile_matches)) class="rounded border">
                        Лот найден в новом профиле
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="notify_shared_price_changes" value="0">
                        <input type="checkbox" name="notify_shared_price_changes" value="1" @checked((bool) old('notify_shared_price_changes', $profile->notify_shared_price_changes)) class="rounded border">
                        Изменение цены в нескольких профилях
                    </label>
                    <div>
                        <label for="notification_frequency" class="mb-1 block text-sm font-medium">Частота уведомлений</label>
                        <select id="notification_frequency" name="notification_frequency" class="w-full rounded border p-2">
                            <option value="immediate" @selected(old('notification_frequency', $profile->notification_frequency) === 'immediate')>Сразу при событии</option>
                            <option value="daily" @selected(old('notification_frequency', $profile->notification_frequency) === 'daily')>Ежедневная сводка в {{ substr($profile->schedule_time, 0, 5) }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Сводка отправляется во время ежедневного запуска профиля (время Бишкека).</p>
                        @error('notification_frequency')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                @include('profiles._aspect-filters', ['filterValues' => [
                    'brand' => $profile->brands ?: ($profile->brand ? [$profile->brand] : []),
                    'processor' => $profile->processors ?: ($profile->processor ? [$profile->processor] : []),
                    'ram_size' => $profile->ram_sizes ?: ($profile->ram_size ? [$profile->ram_size] : ($profile->min_ram_gb ? [$profile->min_ram_gb.' GB'] : [])),
                    'storage_capacity' => $profile->storage_capacities ?: ($profile->storage_capacity ? [$profile->storage_capacity] : []),
                    'screen_size' => $profile->screen_sizes ?: ($profile->screen_size ? [$profile->screen_size] : []),
                    'gpu' => $profile->gpus ?: ($profile->gpu ? [$profile->gpu] : []),
                    'operating_system' => $profile->operating_system,
                ]])

                <div>
                    <input type="hidden" name="active" value="0">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="active" value="1" @checked((bool) old('active', $profile->active)) class="rounded border">
                        Профиль активен и участвует в поиске
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <button class="rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Сохранить изменения</button>
                    <a href="{{ route('profiles.index') }}" class="text-sm text-slate-600 hover:underline">Отмена</a>
                </div>
            </form>
            </div>
        </main>
    </body>
</html>
