<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Сводка по поиску — eBay Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')
            <div class="min-w-0">
                <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Итоги поиска">
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Найдено в последних запусках</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($foundCount) }}</p>
                    </article>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Новых товаров в последних запусках</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($newListingCount) }}</p>
                    </article>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Всего товаров в базе</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($listingCount) }}</p>
                    </article>
                    <a href="{{ route('listings.watchlist') }}" class="rounded-xl bg-white p-5 shadow-sm hover:ring-2 hover:ring-blue-200">
                        <p class="text-sm text-slate-500">В списке «Наблюдение»</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($watchingCount) }}</p>
                    </a>
                    <a href="{{ route('listings.buy') }}" class="rounded-xl bg-white p-5 shadow-sm hover:ring-2 hover:ring-blue-200">
                        <p class="text-sm text-slate-500">В списке покупок</p>
                        <p class="mt-2 text-3xl font-bold">{{ number_format($buyCount) }}</p>
                    </a>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Профилей давно не запускались</p>
                        <p class="mt-2 text-3xl font-bold {{ $overdueProfiles->isNotEmpty() ? 'text-amber-700' : 'text-emerald-700' }}">{{ $overdueProfiles->count() }}</p>
                    </article>
                </section>

                @if ($overdueProfiles->isNotEmpty())
                    <section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4" aria-labelledby="overdue-profiles-heading">
                        <h2 id="overdue-profiles-heading" class="font-semibold text-amber-950">Профили требуют запуска</h2>
                        <ul class="mt-2 list-inside list-disc text-sm text-amber-900">
                            @foreach ($overdueProfiles as $profile)
                                <li>
                                    <a href="{{ route('profiles.edit', $profile) }}" class="font-medium underline">{{ $profile->name }}</a>
                                    —
                                    {{ $profile->last_synced_at
                                        ? 'последний запуск '.$profile->last_synced_at->timezone('Asia/Bishkek')->format('d.m.Y H:i')
                                        : 'ещё не запускался' }}
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="rounded-xl bg-white shadow-sm">
                    <div class="border-b p-4">
                        <h2 class="font-semibold">Результаты по профилям</h2>
                        <p class="mt-1 text-xs text-slate-500">Профиль считается просроченным, если не запускался дольше своего интервала расписания. Отключённые профили не считаются просроченными.</p>
                    </div>
                    @include('partials.table-column-settings', ['tableKey' => 'search-summary'])
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'profile', 'sortKey' => 'profile', 'label' => 'Профиль', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'found', 'sortKey' => 'found', 'label' => 'Последний поиск: найдено', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'new-listings', 'sortKey' => 'new_listings', 'label' => 'Новых товаров', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'total', 'sortKey' => 'total', 'label' => 'В базе', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'watching', 'sortKey' => 'watching', 'label' => 'Наблюдение', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'buy', 'sortKey' => 'buy', 'label' => 'Список покупок', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'last-run', 'sortKey' => 'last_run', 'label' => 'Последний запуск', 'sort' => $sort])
                                    <th scope="col" class="p-3 text-slate-500" data-column-key="schedule" data-column-label="Расписание">Расписание</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'status', 'sortKey' => 'status', 'label' => 'Состояние', 'sort' => $sort])
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($profiles as $profile)
                                    @php($activity = $latestActivities->get($profile->id))
                                    @php($isOverdue = $overdueProfiles->contains('id', $profile->id))
                                    <tr>
                                        <td class="p-3">
                                            <a href="{{ route('profiles.edit', $profile) }}" class="font-medium text-blue-700 hover:underline">{{ $profile->name }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $profile->query }}</p>
                                        </td>
                                        <td class="p-3">{{ $activity ? number_format((int) data_get($activity->metadata, 'found', 0)) : '—' }}</td>
                                        <td class="p-3">{{ $activity ? number_format((int) data_get($activity->metadata, 'new_listings', 0)) : '—' }}</td>
                                        <td class="p-3">{{ number_format($profile->listings_count) }}</td>
                                        <td class="p-3">{{ number_format($profile->watching_listings_count) }}</td>
                                        <td class="p-3">{{ number_format($profile->buy_listings_count) }}</td>
                                        <td class="p-3 text-slate-600">{{ $profile->last_synced_at?->timezone('Asia/Bishkek')->format('d.m.Y H:i') ?? 'Ещё не запускался' }}</td>
                                        <td class="p-3">
                                            @if ($profile->schedule_time)
                                                <span class="text-slate-700">{{ substr($profile->schedule_time, 0, 5) }}</span>
                                                @if ($profile->schedule_interval_hours)
                                                    <span class="text-xs text-slate-500"> каждые {{ $profile->schedule_interval_hours }} ч</span>
                                                @endif
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <span @class([
                                                'rounded-full px-2 py-1 text-xs font-medium',
                                                'bg-slate-100 text-slate-600' => ! $profile->active,
                                                'bg-amber-100 text-amber-900' => $profile->active && $isOverdue,
                                                'bg-emerald-100 text-emerald-800' => $profile->active && ! $isOverdue,
                                            ])>
                                                {{ ! $profile->active ? 'Отключён' : ($isOverdue ? 'Просрочен' : 'По расписанию') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" data-column-empty-state class="p-8 text-center text-slate-500">Профилей поиска пока нет.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
