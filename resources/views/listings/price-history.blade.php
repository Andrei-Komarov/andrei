<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>История цены — Finder</title>
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
                        <a href="{{ route('listings.index') }}" class="text-sm font-medium text-blue-700 hover:underline">← К списку товаров</a>
                        <h1 class="mt-3 text-2xl font-bold">{{ $listing->title }}</h1>
                        <p class="mt-1 text-sm text-slate-600">
                            Профиль: {{ $listing->searchProfiles->pluck('name')->join(', ') }} · Текущая цена с доставкой:
                            {{ $listing->currency }} {{ number_format((float) $listing->total_price, 2) }}
                        </p>
                        @php($primaryProfile = $listing->searchProfiles->first())
                        @if ($primaryProfile && $primaryProfile->price_alert_threshold !== null)
                            <p class="mt-1 text-sm text-slate-600">
                                Целевая цена для уведомления:
                                {{ $listing->currency }} {{ number_format((float) $primaryProfile->price_alert_threshold, 2) }}
                            </p>
                        @endif
                    </header>
                    @include('partials.table-column-settings', ['tableKey' => 'price-history'])
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'changed-at', 'sortKey' => 'date', 'label' => 'Дата изменения', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена с доставкой', 'sort' => $sort])
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($history as $entry)
                                    <tr>
                                        <td class="p-3">{{ $entry->observed_at->format('d.m.Y H:i') }}</td>
                                        <td class="p-3 font-semibold">{{ $entry->currency }} {{ number_format((float) $entry->price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" data-column-empty-state class="p-8 text-center text-slate-500">История цены появится после первого обновления товара.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $history->links() }}</div>
                </section>
            </div>
        </main>
    </body>
</html>
