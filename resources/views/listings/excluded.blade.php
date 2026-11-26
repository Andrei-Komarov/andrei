<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Исключённый товар — Finder</title>
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

                <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <h1 class="text-2xl font-bold">Исключённый товар</h1>
                        <p class="text-sm text-slate-500">Товары, отфильтрованные по словам исключения. Система нашла совпадение в названии лота или описании продавца.</p>
                    </div>

                    <form id="listing-filters-form" method="GET" action="{{ route('listings.excluded') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label for="profile_id" class="text-sm font-medium">
                            Фильтр по профилю
                            <select id="profile_id" name="profile_id" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Все профили</option>
                                @foreach ($profiles as $profile)
                                    <option value="{{ $profile->id }}" @selected((string) request('profile_id') === (string) $profile->id)>
                                        {{ $profile->name }} — товаров: {{ $profile->profile_listings_count }}
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
                                <option value="date_desc" @selected($sort === 'date_desc')>Дата: сначала новые</option>
                                <option value="date_asc" @selected($sort === 'date_asc')>Дата: сначала старые</option>
                                <option value="price_asc" @selected($sort === 'price_asc')>Цена: сначала дешевле</option>
                                <option value="price_desc" @selected($sort === 'price_desc')>Цена: сначала дороже</option>
                                <option value="title_asc" @selected($sort === 'title_asc')>Название: А—Я</option>
                                <option value="title_desc" @selected($sort === 'title_desc')>Название: Я—А</option>
                                <option value="condition_asc" @selected($sort === 'condition_asc')>Состояние: А—Я</option>
                                <option value="condition_desc" @selected($sort === 'condition_desc')>Состояние: Я—А</option>
                                <option value="seller_asc" @selected($sort === 'seller_asc')>Продавец: А—Я</option>
                                <option value="seller_desc" @selected($sort === 'seller_desc')>Продавец: Я—А</option>
                            </select>
                        </label>

                    </form>

                    <form id="listing-search-form" method="GET" action="{{ route('listings.excluded') }}" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                                <option value="title" @selected(($filters['search_in'] ?? 'title') === 'title')>Название товара</option>
                                <option value="seller_description" @selected(($filters['search_in'] ?? 'title') === 'seller_description')>Описание продавца</option>
                            </select>
                        </label>

                    </form>

                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('listings.excluded') }}" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Сбросить фильтры</a>
                        <button type="submit" form="listing-filters-form" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Применить</button>
                    </div>
                </section>

                <section class="rounded-xl bg-white shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 p-4">
                        <form id="bulk-listings-form" method="POST" action="{{ route('listings.bulk-action') }}" class="flex flex-wrap items-end gap-3" data-bulk-listings>
                            @csrf
                            <label for="bulk_action" class="sr-only">Выберите действие для товаров</label>
                            <select id="bulk_action" name="bulk_action" aria-label="Выберите действие для товаров" required class="block rounded border p-2 font-normal">
                                <option value="">Выберите действие</option>
                                <option value="unexclude">Список товаров</option>
                                <option value="new">Статус: Новый</option>
                                <option value="watching">Статус: Наблюдаю</option>
                                <option value="buy">Статус: Купить</option>
                                <option value="skipped">Статус: Пропустить</option>
                                <option value="delete">Переместить в удалённые</option>
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
                            @include('partials.table-column-settings', ['tableKey' => 'excluded', 'containerClass' => 'p-0'])
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="w-8 px-2 py-3" data-column-key="select" data-column-label="Выбор"><input type="checkbox" aria-label="Выбрать все товары на странице" data-select-all-listings></th>
                                    <th class="p-3" data-column-key="image">Фото</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'title', 'sortKey' => 'title', 'label' => 'Лот', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort])
                                    <th class="p-3" data-column-key="reason">Причина</th>
                                    <th class="p-3" data-column-key="profile">Профиль</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'condition', 'sortKey' => 'condition', 'label' => 'Состояние', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'date', 'label' => 'Дата', 'sort' => $sort])
                                    <th class="p-3" data-column-key="seller">Продавец</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'seller-description', 'sortKey' => 'seller_description', 'label' => 'Описание продавца', 'sort' => $sort])
                                    <th class="w-16 px-2 py-3 text-center" data-column-key="checked" data-column-label="Проверено" title="Отметить товар как проверенный">✓</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($listings as $listing)
                                    @php($listingImages = collect($listing->galleryImageUrls()))
                                    <tr data-listing-row data-listing-id="{{ $listing->id }}">
                                        <td class="w-8 px-2 py-3">
                                            @php($listingMatches = $listingGroupsById->get($listing->id, collect([$listing])))
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
                                        <td class="p-3 max-w-xs">
                                            <a href="{{ $listing->item_url }}" target="_blank" rel="noopener noreferrer" class="line-clamp-2 block text-sm font-medium text-blue-700 hover:underline">{{ $listing->title }}</a>
                                        </td>
                                        <td class="p-3">
                                            <span class="font-medium">{{ number_format((float) $listing->total_price, 2) }} {{ $listing->currency }}</span>
                                        </td>
                                        <td class="p-3">
                                            @if ($listing->excluded_reason)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                                    {{ Str::limit($listing->excluded_reason, 50) }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @foreach ($listing->searchProfiles as $profile)
                                                <p class="text-sm">{{ $profile->name ?? '—' }}</p>
                                            @endforeach
                                        </td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                {{ $listing->condition ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <p class="text-xs text-slate-500">{{ $listing->created_at->format('d.m.Y H:i') }}</p>
                                        </td>
                                        <td class="p-3">
                                            <p class="text-sm">{{ $listing->seller ?? '—' }}</p>
                                        </td>
                                        <td class="max-w-sm p-3 text-xs" data-listing-seller-description>
                                            <div class="max-h-40 overflow-y-auto whitespace-pre-line break-words">{{ $listing->seller_description ?: '—' }}</div>
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
                                        <td colspan="11" data-column-empty-state class="p-8 text-center text-slate-500">Исключённых товаров пока нет.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            @if ($showPerPageSelector)
                                <form method="GET" action="{{ route('listings.excluded', request()->except(['page', 'per_page'])) }}" class="flex items-center gap-2">
                                    <label for="excluded-listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                    <select
                                        id="excluded-listings-per-page"
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
                            <p class="text-sm text-slate-700">
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
                            <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
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
