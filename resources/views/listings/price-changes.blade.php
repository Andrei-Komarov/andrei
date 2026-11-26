<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Изменение цен — Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
                <section class="rounded-xl bg-white shadow-sm">
                    <header class="p-4">
                        <h1 class="text-2xl font-bold">Изменение цен</h1>
                        <p class="text-sm text-slate-500">Товары, у которых изменилась цена при повторном парсинге.</p>
                    </header>

                    <div class="border-t border-slate-200 p-4">
                        <form method="GET" action="{{ route('listings.price-changes') }}" class="flex flex-wrap items-center gap-3">
                            @if ($sort !== 'date_desc')
                                <input type="hidden" name="sort" value="{{ $sort }}">
                            @endif
                            @if ($perPage !== 25)
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                            @endif
                            <label for="status-filter" class="text-sm font-medium text-slate-700">Статус:</label>
                            <select
                                id="status-filter"
                                name="status"
                                class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                onchange="this.form.submit()"
                            >
                                <option value="active" @selected($status === 'active')>Активные</option>
                                <option value="excluded" @selected($status === 'excluded')>Исключённые</option>
                                <option value="all" @selected($status === 'all')>Все</option>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="p-3" data-column-key="image">Фото</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'title', 'sortKey' => 'title', 'label' => 'Название', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Текущая цена', 'sort' => $sort])
                                    <th class="p-3" data-column-key="previous">Предыдущая цена</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'change', 'sortKey' => 'change', 'label' => 'Изменение', 'sort' => $sort])
                                    <th class="p-3" data-column-key="profile">Профиль</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'date', 'label' => 'Дата изменения', 'sort' => $sort])
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($listings as $listing)
                                    @php
                                        $history = $listing->priceHistory->sortByDesc('observed_at')->sortByDesc('id')->values();
                                        $currentPrice = $listing->total_price;
                                        $previousPrice = $history->count() >= 2 ? (float) $history[1]->price : null;
                                        $lastChangeDate = $history->count() >= 2 ? $history[1]->observed_at : $listing->updated_at;
                                        $changePercent = $previousPrice !== null && $previousPrice > 0
                                            ? round((($currentPrice - $previousPrice) / $previousPrice) * 100, 2)
                                            : null;
                                        $isPriceDrop = $changePercent !== null && $changePercent < 0;
                                        $isPriceIncrease = $changePercent !== null && $changePercent > 0;
                                    @endphp
                                    <tr data-listing-row data-listing-id="{{ $listing->id }}">
                                        <td class="p-3">
                                            @if ($listing->image_url)
                                                <img src="{{ $listing->image_url }}" alt="" class="h-12 w-16 object-cover rounded" loading="lazy">
                                            @else
                                                <span class="text-xs text-slate-400">Нет фото</span>
                                            @endif
                                        </td>
                                        <td class="p-3 max-w-xs">
                                            <a href="{{ $listing->item_url }}" target="_blank" rel="noopener noreferrer" class="line-clamp-2 block text-sm font-medium text-blue-700 hover:underline">{{ $listing->title }}</a>
                                        </td>
                                        <td class="p-3">
                                            <span class="font-medium">{{ number_format((float) $currentPrice, 2) }} {{ $listing->currency }}</span>
                                        </td>
                                        <td class="p-3">
                                            @if ($previousPrice !== null)
                                                <span class="text-slate-500">{{ number_format($previousPrice, 2) }} {{ $listing->currency }}</span>
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @if ($changePercent !== null)
                                                @if ($isPriceDrop)
                                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                                                        ↓ {{ number_format($changePercent, 2) }}%
                                                    </span>
                                                @elseif ($isPriceIncrease)
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                                        ↑ {{ number_format($changePercent, 2) }}%
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                                        0.00%
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <p class="text-sm">{{ $listing->searchProfiles->pluck('name')->join(', ') ?? '—' }}</p>
                                        </td>
                                        <td class="p-3">
                                            <p class="text-xs text-slate-500">{{ $lastChangeDate->format('d.m.Y H:i') }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" data-column-empty-state class="p-8 text-center text-slate-500">Изменений цен пока нет. Они появятся после повторного парсинга товаров.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            @if ($showPerPageSelector)
                                <form method="GET" action="{{ route('listings.price-changes', request()->except(['page', 'per_page'])) }}" class="flex items-center gap-2">
                                    <label for="price-changes-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                    <select
                                        id="price-changes-per-page"
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
    </body>
</html>
