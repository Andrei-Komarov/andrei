<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Удалённые товары — Finder</title>
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
                @if ($errors->any())
                    <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <section class="rounded-xl bg-white shadow-sm">
                    <header class="p-4">
                        <h1 class="text-2xl font-bold">Удалённые товары</h1>
                        <p class="text-sm text-slate-500">Удалённые товары можно восстановить в список.</p>
                    </header>

                    <form id="deleted-listings-bulk-delete" method="POST" action="{{ route('listings.bulk-force-destroy') }}"
                        class="flex flex-wrap items-center gap-3 px-4 pb-4"
                        data-confirm-dialog-form
                        data-confirm-message="bulk-delete"
                        data-confirm-button="Удалить навсегда">
                        @csrf
                        <button type="submit" class="rounded border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                            Удалить выбранные навсегда
                        </button>
                        <span class="text-sm text-slate-500" data-deleted-selection-count></span>
                        <div class="ml-auto">
                            @include('partials.table-column-settings', ['tableKey' => 'deleted-listings', 'containerClass' => 'p-0'])
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="w-8 p-2" data-column-key="selection">
                                        <input type="checkbox" class="h-4 w-4" data-deleted-select-all aria-label="Выбрать все товары">
                                    </th>
                                    <th class="p-3" data-column-key="image">Фото</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'listing', 'sortKey' => 'title', 'label' => 'Товар', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'deleted', 'sortKey' => 'deleted', 'label' => 'Удалён', 'sort' => $sort])
                                    <th class="p-3" data-column-key="actions">Действие</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($listings as $listing)
                                    @php($listingImages = collect($listing->galleryImageUrls()))
                                    <tr>
                                        <td class="w-8 p-2">
                                            <input type="checkbox" class="h-4 w-4" name="listing_ids[]" value="{{ $listing->id }}"
                                                form="deleted-listings-bulk-delete" data-deleted-listing-select
                                                aria-label="Выбрать {{ $listing->title }}">
                                        </td>
                                        <td class="p-3">
                                            @if ($listingImages->isNotEmpty())
                                                <button
                                                    type="button"
                                                    class="block rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    aria-label="Открыть фотографии товара: {{ $listing->title }}"
                                                    data-listing-image-gallery
                                                    data-image-gallery="{{ json_encode($listingImages, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                                    data-image-title="{{ $listing->title }}"
                                                >
                                                    <img src="{{ $listing->image_url ?: $listingImages->first() }}" alt="Фото товара: {{ $listing->title }}" loading="lazy" class="h-16 w-20 rounded border border-slate-200 object-contain">
                                                </button>
                                            @else
                                                <span class="text-xs text-slate-400">Нет фото</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <a class="font-medium text-blue-700 hover:underline" href="{{ $listing->item_url }}" target="_blank" rel="noopener">{{ $listing->title }}</a>
                                            <div class="text-xs text-slate-500">{{ $listing->searchProfiles->pluck('name')->join(', ') }}</div>
                                        </td>
                                        <td class="p-3 font-semibold">${{ number_format((float) $listing->total_price, 2) }}</td>
                                        <td class="p-3 text-xs text-slate-600">{{ $listing->deleted_at->format('d.m.Y H:i') }}</td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('listings.restore', $listing->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Восстановить</button>
                                            </form>
                                            <form method="POST" action="{{ route('listings.force-destroy', $listing->id) }}"
                                                data-confirm-dialog-form
                                                data-confirm-button="Удалить навсегда"
                                                data-confirm-message="Удалить товар окончательно? Это действие нельзя отменить.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Удалить навсегда</button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" data-column-empty-state class="p-8 text-center text-slate-500">Удалённых товаров пока нет.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            @if ($showPerPageSelector)
                                <form method="GET" action="{{ route('listings.deleted', request()->except(['page', 'per_page'])) }}" class="flex items-center gap-2">
                                    <label for="deleted-listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                    <select
                                        id="deleted-listings-per-page"
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
                            <p class="text-sm text-slate-700" data-deleted-listings-pagination-count>
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
                            <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" data-deleted-listings-pagination>
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
