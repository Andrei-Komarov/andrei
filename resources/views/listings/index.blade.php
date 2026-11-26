<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $listingSectionTitle }} — Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
            <div data-listing-action-status class="mb-4 hidden rounded p-3" role="status" aria-live="polite"></div>
            @if (session('success'))
                <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
            @endif
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

            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold">{{ $listingSectionTitle }}</h1>
                    <p class="text-sm text-slate-500">{{ $listingSectionDescription }}</p>
                </div>

                <form id="listing-filters-form" method="GET" action="{{ route('listings.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label for="profile_id" class="text-sm font-medium">
                        Фильтр по профилю
                        <select id="profile_id" name="profile_id" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Все профили</option>
                            @foreach ($profiles as $profile)
                                <option value="{{ $profile->id }}" @selected((string) request('profile_id') === (string) $profile->id)>
                                    {{ $profile->name }} — товаров: {{ $profile->listings_count }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label for="status" class="text-sm font-medium">
                        Статус
                        <select id="status" name="status" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Любой статус</option>
                            <option value="new" @selected(($filters['status'] ?? '') === 'new')>Новый</option>
                            <option value="watching" @selected(($filters['status'] ?? '') === 'watching')>Наблюдаю</option>
                            <option value="buy" @selected(($filters['status'] ?? '') === 'buy')>Купить</option>
                            <option value="skipped" @selected(($filters['status'] ?? '') === 'skipped')>Пропустить</option>
                        </select>
                    </label>

                    <label for="price_min" class="text-sm font-medium">
                        Цена от, USD
                        <input id="price_min" name="price_min" type="number" min="0" step="0.01" value="{{ $filters['price_min'] ?? '' }}" class="mt-1 w-full rounded border p-2 font-normal">
                    </label>

                    <label for="price_max" class="text-sm font-medium">
                        Цена до, USD
                        <input id="price_max" name="price_max" type="number" min="0" step="0.01" value="{{ $filters['price_max'] ?? '' }}" class="mt-1 w-full rounded border p-2 font-normal">
                    </label>

                    <label for="condition" class="text-sm font-medium">
                        Состояние
                        <select id="condition" name="condition" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Любое состояние</option>
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition }}" @selected(($filters['condition'] ?? '') === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label for="brand" class="text-sm font-medium">
                        Бренд
                        <select id="brand" name="brand" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Любой бренд</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label for="processor" class="text-sm font-medium">
                        Процессор
                        <select id="processor" name="processor" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Любой процессор</option>
                            @foreach ($processors as $processor)
                                <option value="{{ $processor }}" @selected(($filters['processor'] ?? '') === $processor)>{{ $processor }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label for="ram" class="text-sm font-medium">
                        Объём RAM
                        <select id="ram" name="ram" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Любой объём</option>
                            @foreach ($rams as $ram)
                                <option value="{{ $ram }}" @selected((int) ($filters['ram'] ?? 0) === $ram)>{{ $ram }} GB</option>
                            @endforeach
                        </select>
                    </label>

                    <label for="date_from" class="text-sm font-medium">
                        Добавлено с
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded border p-2 font-normal">
                    </label>

                    <label for="date_to" class="text-sm font-medium">
                        Добавлено по
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded border p-2 font-normal">
                    </label>

                    <label for="sort" class="text-sm font-medium">
                        Сортировка
                        <select id="sort" name="sort" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="price_asc" @selected($sort === 'price_asc')>Цена: сначала дешевле</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Цена: сначала дороже</option>
                            <option value="condition_asc" @selected($sort === 'condition_asc')>Состояние: А—Я</option>
                            <option value="condition_desc" @selected($sort === 'condition_desc')>Состояние: Я—А</option>
                            <option value="date_desc" @selected($sort === 'date_desc')>Дата: сначала новые</option>
                            <option value="date_asc" @selected($sort === 'date_asc')>Дата: сначала старые</option>
                        </select>
                    </label>

                </form>

                <form id="listing-search-form" method="GET" action="{{ route('listings.index') }}" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <input type="hidden" name="profile_id" value="{{ $filters['profile_id'] ?? '' }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                    <input type="hidden" name="price_min" value="{{ $filters['price_min'] ?? '' }}">
                    <input type="hidden" name="price_max" value="{{ $filters['price_max'] ?? '' }}">
                    <input type="hidden" name="condition" value="{{ $filters['condition'] ?? '' }}">
                    <input type="hidden" name="brand" value="{{ $filters['brand'] ?? '' }}">
                    <input type="hidden" name="processor" value="{{ $filters['processor'] ?? '' }}">
                    <input type="hidden" name="ram" value="{{ $filters['ram'] ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    <label for="search" class="text-sm font-medium">
                        Поиск
                        <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Введите текст для поиска" class="mt-1 w-full rounded border p-2 font-normal">
                    </label>

                    <label for="search_in" class="text-sm font-medium">
                        Искать в
                        <select id="search_in" name="search_in" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="title" @selected(($filters['search_in'] ?? 'title') === 'title')>Название лота</option>
                            <option value="seller_description" @selected(($filters['search_in'] ?? '') === 'seller_description')>Описание продавца</option>
                        </select>
                    </label>

                </form>

                <div class="mt-4 flex justify-end gap-3">
                    <a href="{{ route('listings.index') }}" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Сбросить фильтры</a>
                    <button type="submit" form="listing-filters-form" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Применить</button>
                </div>

                @if ($selectedProfile)
                    <p class="mt-3 text-sm text-slate-700">
                        В профиле «{{ $selectedProfile->name }}» всего товаров: <strong>{{ $selectedProfile->listings_count }}</strong>.
                        @if ($listings->total() !== $selectedProfile->listings_count)
                            Сейчас в таблице показано {{ $listings->total() }} товаров с учётом остальных фильтров.
                        @endif
                    </p>
                @endif
            </section>

            <section class="rounded-xl bg-white shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 p-4">
                    <form id="bulk-listings-form" method="POST" action="{{ route('listings.bulk-action') }}" class="flex flex-wrap items-end gap-3" data-bulk-listings>
                        @csrf
                        <label for="bulk_action" class="sr-only">Выберите действие для товаров</label>
                        <select id="bulk_action" name="bulk_action" aria-label="Выберите действие для товаров" required class="block rounded border p-2 font-normal">
                            <option value="">Выберите действие</option>
                            @if (($filters['status'] ?? '') === 'buy')
                                <option value="unbuy">Вернуть в список</option>
                                <option value="delete">Переместить в удалённые</option>
                            @else
                                <option value="new">Статус: Новый</option>
                                <option value="watching">Статус: Наблюдаю</option>
                                <option value="buy">Статус: Купить</option>
                                <option value="skipped">Статус: Пропустить</option>
                                <option value="delete">Переместить в удалённые</option>
                            @endif
                        </select>
                        <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50" data-bulk-submit disabled>Применить</button>
                        <span class="text-sm text-slate-500" data-bulk-selection-count role="status" aria-live="polite"></span>
                    </form>
                    <div class="ml-auto flex items-center gap-2">
                        <form method="POST" action="{{ route('listings.export') }}" data-export-listings>
                            @csrf
                            <button type="submit" aria-label="Скачать выбранные CSV (Excel)" title="Скачать выбранные CSV (Excel)" class="rounded border border-emerald-700 p-2 text-emerald-800 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50" data-export-submit disabled>
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4" />
                                </svg>
                            </button>
                        </form>
                        @include('partials.table-column-settings', ['tableKey' => 'listings', 'containerClass' => 'p-0'])
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="w-8 px-2 py-3" data-column-key="select" data-column-label="Выбор"><input type="checkbox" aria-label="Выбрать все товары на странице" data-select-all-listings></th>
                                <th class="p-3" data-column-key="image">Фото</th>
                                @include('partials.sortable-table-heading', ['columnKey' => 'listing', 'sortKey' => 'title', 'label' => 'Лот', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'brand', 'sortKey' => 'brand', 'label' => 'Бренд', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'statuses', 'sortKey' => 'status', 'label' => 'Статусы', 'sort' => $sort, 'class' => 'w-36 min-w-36 p-3'])
                                @include('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'value', 'sortKey' => 'value', 'label' => 'Оценка выгодности', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'condition', 'sortKey' => 'condition', 'label' => 'Состояние', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'added', 'sortKey' => 'date', 'label' => 'Дата появления', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'specifications', 'sortKey' => 'specifications', 'label' => 'Характеристики', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'seller', 'sortKey' => 'seller', 'label' => 'Продавец', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'seller-description', 'sortKey' => 'seller_description', 'label' => 'Описание продавца', 'sort' => $sort])
                                @include('partials.sortable-table-heading', ['columnKey' => 'note', 'sortKey' => 'note', 'label' => 'Заметка к товару', 'sort' => $sort])
                                <th class="w-16 px-2 py-3 text-center" data-column-key="checked" data-column-label="Проверено" title="Отметить товар как проверенный">✓</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" data-listings-tbody data-current-status="{{ $filters['status'] ?? '' }}">
                            @forelse ($listings as $listing)
                                @php($listingMatches = $listingGroupsById->get($listing->id, collect([$listing])))
                                @php($listingImages = collect($listing->galleryImageUrls()))
                                <tr data-listing-row data-listing-id="{{ $listing->id }}" data-listing-status="{{ $listing->status }}">
                                    <td class="w-8 px-2 py-3">
                                        <input
                                            type="checkbox"
                                            name="listing_ids[]"
                                            value="{{ $listing->id }}"
                                            data-listing-ids='@json($listingMatches->pluck("id")->all())'
                                            form="bulk-listings-form"
                                            aria-label="Выбрать товар: {{ $listing->title }}"
                                            data-listing-select
                                        >
                                    </td>
                                    <td class="p-3">
                                        @if ($listingImages->isNotEmpty())
                                            <button
                                                type="button"
                                                class="block h-16 w-20 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                aria-label="Открыть фотографии товара: {{ $listing->title }}"
                                                data-listing-image-gallery
                                                data-image-gallery="{{ json_encode($listingImages, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                                data-image-title="{{ $listing->title }}"
                                            >
                                                <img src="{{ $listing->image_url ?: $listingImages->first() }}" alt="Фото товара: {{ $listing->title }}" loading="lazy" width="80" height="64" class="h-full w-full rounded border border-slate-200 bg-slate-200 object-contain" style="aspect-ratio:80/64;">
                                            </button>
                                        @else
                                            <div class="flex h-16 w-20 items-center justify-center">
                                                <span class="text-xs text-slate-400">Нет фото</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <a class="font-medium text-blue-700 hover:underline" href="{{ $listing->item_url }}" target="_blank" rel="noopener">{{ $listing->title }}</a>
                                        <details class="mt-1 text-xs text-slate-600">
                                            <summary class="cursor-pointer font-medium text-violet-700 hover:text-violet-900">
                                                Найден в {{ $listing->searchProfiles->count() }} {{ $listing->searchProfiles->count() === 1 ? 'профиле' : 'профилях' }}
                                            </summary>
                                            <ul class="mt-2 space-y-2">
                                                @foreach ($listing->searchProfiles as $profile)
                                                    <li class="rounded border border-slate-200 p-2">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <a href="{{ route('profiles.edit', $profile) }}" class="font-medium text-blue-700 hover:underline">{{ $profile->name }}</a>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </td>
                                    @php($listingBrand = $displaySpecifications->get($listing->id)['brand'] ?? null)
                                    <td class="p-3" data-listing-brand>
                                        {{ $listingBrand ?: '—' }}
                                    </td>
                                    <td class="w-36 min-w-36 p-3">
                                        @php($listingStatusScope = $listing->searchProfiles->count() > 1 ? 'Все профили' : $listing->searchProfiles->first()->name)
                                        @php($listingCurrentStatus = $listing->status)
                                        <div data-profile-listing-id="{{ $listing->id }}" data-status="{{ $listingCurrentStatus }}" class="min-w-0">
                                            @if ($listing->searchProfiles->count() > 1)
                                                <span class="mb-1 block text-xs font-medium text-slate-600">{{ $listingStatusScope }}</span>
                                            @endif
                                            <form method="POST" action="{{ route('listings.update', array_merge(request()->query(), ['listing' => $listing])) }}" class="flex shrink-0 flex-nowrap gap-0.5" data-listing-status-form>
                                                @csrf
                                                @method('PATCH')
                                                @foreach ([
                                                                'new' => ['Новый', 'M12 3l1.8 6.2L20 11l-6.2 1.8L12 19l-1.8-6.2L4 11l6.2-1.8L12 3Zm7 12 .7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15Z'],
                                                                'watching' => ['Наблюдаю', 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
                                                                'buy' => ['Купить', 'M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2m-3 5v4m-2-2h4'],
                                                                'skipped' => ['Пропустить', 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM7 12h10'],
                                                ] as $statusValue => [$statusLabel, $iconPath])
                                                    <button
                                                        type="submit"
                                                        name="status"
                                                        value="{{ $statusValue }}"
                                                        title="{{ $listingStatusScope }}: установить статус «{{ $statusLabel }}»"
                                                        aria-label="{{ $listingStatusScope }}: установить статус «{{ $statusLabel }}»"
                                                        @disabled($listingCurrentStatus === $statusValue)
                                                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border {{ $listingCurrentStatus === $statusValue ? 'border-blue-200 bg-blue-50 font-semibold text-blue-800' : 'border-slate-300 text-slate-600 hover:bg-slate-100' }}"
                                                    >
                                                        <svg class="h-3.5 w-3.5" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="{{ $iconPath }}" />
                                                        </svg>
                                                        <span class="sr-only">{{ $statusLabel }}</span>
                                                    </button>
                                                @endforeach
                                            </form>
                                        </div>
                                    </td>
                                    <td class="p-3 font-semibold">
                                        ${{ number_format((float) $listing->total_price, 2) }}
                                        <div class="text-xs font-normal text-slate-500">товар ${{ number_format((float) $listing->price, 2) }} + доставка {{ $listing->shipping_type === 'CALCULATED' ? 'CALCULATED' : '$' . number_format((float) $listing->shipping_price, 2) }}</div>
                                        <a href="{{ route('listings.price-history', $listing) }}" class="mt-1 block text-xs font-normal text-blue-700 hover:underline">История цены</a>
                                    </td>
                                    @php($assessment = $valueAssessments[$listing->id])
                                    <td class="max-w-64 p-3">
                                        @if ($assessment['score'] === null)
                                            <span class="font-medium text-slate-600">Недостаточно данных</span>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Найдено похожих товаров: {{ $assessment['comparison_count'] }} из 3 необходимых.
                                            </p>
                                        @else
                                            <span @class([
                                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800' => $assessment['score'] >= 75,
                                                'bg-amber-100 text-amber-800' => $assessment['score'] >= 50 && $assessment['score'] < 75,
                                                'bg-red-100 text-red-800' => $assessment['score'] < 50,
                                            ])>
                                                {{ $assessment['score'] }} / 100
                                            </span>
                                            <details class="mt-1 text-xs text-slate-600">
                                                <summary class="cursor-pointer hover:text-blue-700">
                                                    Факторы · аналогов: {{ $assessment['comparison_count'] }}
                                                </summary>
                                                <ul class="mt-2 space-y-2">
                                                    @foreach ($assessment['factors'] as $factor)
                                                        <li>
                                                            <span class="font-medium">{{ $factor['label'] }}: {{ $factor['score'] }}/{{ $factor['maximum'] }}</span>
                                                            <span class="block">{{ $factor['explanation'] }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="p-3">{{ $listing->condition ?? '—' }}</td>
                                    <td class="p-3 text-xs text-slate-600">{{ $listing->created_at->format('d.m.Y H:i') }}</td>
                                    @php($specifications = $displaySpecifications[$listing->id])
                                    <td class="p-3 text-xs" data-listing-specifications>
                                        @if (filled($listing->specifications))
                                            <dl class="grid min-w-48 grid-cols-[auto_1fr] gap-x-2 gap-y-1">
                                                @foreach ($listing->specifications as $aspectName => $aspectValue)
                                                    <dt class="font-medium">{{ $aspectName }}:</dt>
                                                    <dd class="break-words">{{ is_array($aspectValue) ? implode(', ', array_filter($aspectValue, 'is_scalar')) : ($aspectValue ?? '—') }}</dd>
                                                @endforeach
                                            </dl>
                                        @else
                                            CPU: {{ $specifications['cpu'] ?? '—' }}<br>
                                            RAM: {{ $specifications['ram_gb'] ? $specifications['ram_gb'].' GB' : '—' }} · SSD: {{ $specifications['storage'] ?? '—' }}<br>
                                            Экран: {{ $specifications['screen'] ?? '—' }} · GPU: {{ $specifications['gpu'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        {{ $listing->seller ?? '—' }}
                                        <div class="text-xs text-slate-500">{{ $listing->seller_feedback ? $listing->seller_feedback.'%' : '' }}</div>
                                    </td>
                                    <td class="max-w-sm p-3 text-xs" data-listing-seller-description>
                                        <div class="max-h-40 overflow-y-auto whitespace-pre-line break-words">{{ $listing->seller_description ?: '—' }}</div>
                                    </td>
                                    <td class="p-3">
                                        <form
                                            method="POST"
                                            action="{{ route('listings.notes.update', $listing) }}"
                                            class="space-y-2"
                                            data-listing-note-form
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <label for="listing-note-{{ $listing->id }}" class="sr-only">Заметка к товару: {{ $listing->title }}</label>
                                            <textarea
                                                id="listing-note-{{ $listing->id }}"
                                                name="note"
                                                rows="3"
                                                maxlength="2000"
                                                placeholder="Например, проверить продавца"
                                                class="w-full rounded border border-slate-300 p-2 text-sm font-normal"
                                                data-listing-note
                                            >{{ old('note', $listingNotesByEbayItemId[$listing->ebay_item_id] ?? '') }}</textarea>
                                            <span class="block text-xs text-slate-500" role="status" aria-live="polite" data-note-save-status></span>
                                        </form>
                                    </td>
                                    <td class="w-16 px-2 py-3 text-center">
                                        <label class="inline-flex cursor-pointer items-center justify-center" title="{{ $listing->checked ? 'Снять отметку' : 'Отметить как проверенный' }}">
                                            <input
                                                type="checkbox"
                                                class="sr-only peer"
                                                data-listing-checked
                                                data-listing-id="{{ $listing->id }}"
                                                {{ $listing->checked ? 'checked' : '' }}
                                                aria-label="{{ $listing->checked ? 'Товар проверен' : 'Товар не проверен' }}"
                                            >
                                            <span class="flex h-6 w-6 items-center justify-center rounded border border-slate-300 text-sm text-slate-400 transition-colors hover:border-blue-400 hover:text-blue-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-600">
                                                {{ $listing->checked ? '✓' : '' }}
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" data-column-empty-state class="p-8 text-center text-slate-500">Лотов пока нет. Создайте профиль и запустите <code>php artisan ebay:import-laptops</code>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        @if ($showPerPageSelector)
                            <form method="GET" action="{{ route('listings.index', request()->except(['page', 'per_page'])) }}" class="flex items-center gap-2">
                                <label for="listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                <select
                                    id="listings-per-page"
                                    name="per_page"
                                    class="rounded border border-slate-300 bg-white px-2 py-1 text-sm"
                                    onchange="this.form.submit()"
                                >
                                    @foreach ([25, 50, 100, 200] as $pageSize)
                                        <option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ $pageSize }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <p class="text-sm text-slate-700" data-listings-pagination-count>
                            {!! __('Showing') !!}
                            @if ($listings->firstItem())
                                <span class="font-medium">{{ $listings->firstItem() }}</span>
                                {!! __('to') !!}
                                <span class="font-medium">{{ $listings->lastItem() }}</span>
                            @else
                                {{ $listings->count() }}
                            @endif
                            {!! __('of') !!}
                            <span class="font-medium">{{ $listings->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                    @if ($listings->hasPages())
                        @php($visibleLastPage = min($listings->lastPage(), max(5, $listings->currentPage() + 4)))
                        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" data-listings-pagination>
                            <div class="flex items-center">
                                @if ($listings->onFirstPage())
                                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="rounded-l border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-400">‹</span>
                                @else
                                    <a href="{{ $listings->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="rounded-l border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">‹</a>
                                @endif
                                @foreach ($listings->getUrlRange(1, $visibleLastPage) as $page => $url)
                                    @if ($page === $listings->currentPage())
                                        <span aria-current="page" data-page-number="{{ $page }}" class="border-y border-slate-300 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" data-page-number="{{ $page }}" class="border-y border-r border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ $page }}</a>
                                    @endif
                                @endforeach
                                @if ($listings->hasMorePages())
                                    <a href="{{ $listings->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="rounded-r border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">›</a>
                                @else
                                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="rounded-r border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-400">›</span>
                                @endif
                            </div>
                        </nav>
                    @endif
                </div>
            </section>

            </div>
        </main>
        @include('partials.listing-image-gallery')
        @include('partials.confirm-dialog')
    </body>
</html>
