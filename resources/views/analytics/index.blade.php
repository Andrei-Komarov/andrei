<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Статистика и аналитика — Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0">
                <div class="mx-auto max-w-7xl space-y-6 p-6">
        {{-- Header with period selector --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Статистика и аналитика</h1>
                <p class="text-sm text-slate-500">Обзор товаров, импорта и цен</p>
            </div>
            <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center gap-2">
                <label for="period" class="text-sm text-slate-600">Период:</label>
                <select name="period" id="period" class="rounded border border-slate-300 px-3 py-1.5 text-sm" onchange="this.form.submit()">
                    <option value="7" {{ $period == 7 ? 'selected' : '' }}>7 дней</option>
                    <option value="30" {{ $period == 30 ? 'selected' : '' }}>30 дней</option>
                    <option value="90" {{ $period == 90 ? 'selected' : '' }}>90 дней</option>
                </select>
            </form>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Всего товаров</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total_products']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Новых</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['new_products']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Наблюдаю</p>
                <p class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($stats['watching_products']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Список покупок</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['buy_products']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Исключено</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($stats['excluded_products']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Средняя цена</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">${{ number_format($stats['avg_price'], 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Диапазон цен</p>
                <p class="mt-1 text-sm font-bold text-slate-900">${{ number_format($stats['min_price'], 0) }} – ${{ number_format($stats['max_price'], 0) }}</p>
            </div>
        </div>

        {{-- Charts Row 1: Brands + Condition --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Товары по брендам</h2>
                <div class="h-[150px]"><canvas id="brandsChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        @foreach($productsByBrand as $item)
                        <div class="flex items-center justify-between rounded bg-slate-50 px-2 py-1">
                            <span class="text-slate-600">{{ $item->brand ?: 'Без бренда' }}</span>
                            <span class="font-semibold text-slate-900">{{ number_format($item->count) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Товары по состоянию</h2>
                <div class="h-[150px]"><canvas id="conditionChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        @foreach($productsByCondition as $item)
                        <div class="flex items-center justify-between rounded bg-slate-50 px-2 py-1">
                            <span class="text-slate-600">{{ match($item->condition) { 'New' => 'Новый', 'Refurbished' => 'Восстановленный', 'Used' => 'Б/у', default => $item->condition } }}</span>
                            <span class="font-semibold text-slate-900">{{ number_format($item->count) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        {{-- Charts Row 2: Price Distribution + Price Trend --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Распределение по цене</h2>
                <div class="h-[150px]"><canvas id="priceDistributionChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        @foreach($priceDistribution as $range)
                        <div class="flex items-center justify-between rounded bg-slate-50 px-2 py-1">
                            <span class="text-slate-600">{{ $range['label'] }}</span>
                            <span class="font-semibold text-slate-900">{{ number_format($range['count']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Средняя цена (тренд)</h2>
                <div class="h-[150px]"><canvas id="priceTrendChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Средняя</span>
                            <span class="font-semibold text-slate-900">${{ number_format($priceTrendAvg->average ?? 0, 2) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Минимум</span>
                            <span class="font-semibold text-slate-900">${{ number_format($priceTrendAvg->min ?? 0, 2) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Максимум</span>
                            <span class="font-semibold text-slate-900">${{ number_format($priceTrendAvg->max ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Charts Row 3: Import Stats + Profile Activity --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Импорт и парсинг (7 дней)</h2>
                <div class="h-[150px]"><canvas id="importChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded bg-emerald-50 px-2 py-1 text-center">
                            <span class="block text-emerald-600">Завершено</span>
                            <span class="font-semibold text-emerald-700">{{ number_format(array_sum($importCompleted)) }}</span>
                        </div>
                        <div class="rounded bg-red-50 px-2 py-1 text-center">
                            <span class="block text-red-600">Ошибки</span>
                            <span class="font-semibold text-red-700">{{ number_format(array_sum($importFailed)) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Всего</span>
                            <span class="font-semibold text-slate-900">{{ number_format(array_sum($importCompleted) + array_sum($importFailed)) }}</span>
                        </div>
                    </div>
                </div>
            </section>
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Активность профилей</h2>
                <div class="h-[150px]"><canvas id="profileChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="space-y-1 text-xs">
                        @foreach($profileActivity as $profile)
                        <div class="flex items-center justify-between rounded bg-slate-50 px-2 py-1">
                            <span class="text-slate-600">{{ $profile->name }}</span>
                            <div class="flex gap-2">
                                <span class="font-semibold text-blue-600">{{ number_format($profile->total_count) }}</span>
                                @if($profile->new_count > 0)
                                <span class="font-semibold text-emerald-600">+{{ number_format($profile->new_count) }}</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        {{-- Charts Row 4: New Items + Price Drops --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Новые товары</h2>
                <div class="h-[150px]"><canvas id="newItemsChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded bg-emerald-50 px-2 py-1 text-center">
                            <span class="block text-emerald-600">За период</span>
                            <span class="font-semibold text-emerald-700">{{ number_format(array_sum($newItemCounts)) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">В день (среднее)</span>
                            <span class="font-semibold text-slate-900">{{ number_format(count($newItemCounts) > 0 ? array_sum($newItemCounts) / count($newItemCounts) : 0, 1) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Максимум</span>
                            <span class="font-semibold text-slate-900">{{ number_format(max($newItemCounts) ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </section>
            <section class="rounded-xl bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-base font-bold">Изменения цен</h2>
                <div class="h-[150px]"><canvas id="priceDropsChart"></canvas></div>
                <div class="mt-3 border-t border-slate-200 pt-3">
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded bg-amber-50 px-2 py-1 text-center">
                            <span class="block text-amber-600">За период</span>
                            <span class="font-semibold text-amber-700">{{ number_format(array_sum($priceDropCounts)) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">В день (среднее)</span>
                            <span class="font-semibold text-slate-900">{{ number_format(count($priceDropCounts) > 0 ? array_sum($priceDropCounts) / count($priceDropCounts) : 0, 1) }}</span>
                        </div>
                        <div class="rounded bg-slate-50 px-2 py-1 text-center">
                            <span class="block text-slate-500">Максимум</span>
                            <span class="font-semibold text-slate-900">{{ number_format(max($priceDropCounts) ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
    (function() {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'];

        // Brands Doughnut
        new Chart(document.getElementById('brandsChart'), {
            type: 'doughnut',
            data: {
                labels: @json($brandLabels),
                datasets: [{ data: @json($brandCounts), backgroundColor: colors }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        // Condition Doughnut
        new Chart(document.getElementById('conditionChart'), {
            type: 'doughnut',
            data: {
                labels: @json($conditionLabels),
                datasets: [{ data: @json($conditionCounts), backgroundColor: ['#10b981', '#f59e0b', '#6b7280'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        // Price Distribution Bar
        const priceDist = @json($priceDistribution);
        new Chart(document.getElementById('priceDistributionChart'), {
            type: 'bar',
            data: {
                labels: priceDist.map(d => d.label),
                datasets: [{ label: 'Товаров', data: priceDist.map(d => d.count), backgroundColor: '#3b82f6' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Price Trend Line
        new Chart(document.getElementById('priceTrendChart'), {
            type: 'line',
            data: {
                labels: @json($priceTrendDates),
                datasets: [{
                    label: 'Средняя цена ($)',
                    data: @json($priceTrendAvg),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false } } }
        });

        // Import Stats Bar
        new Chart(document.getElementById('importChart'), {
            type: 'bar',
            data: {
                labels: @json($importDates),
                datasets: [
                    { label: 'Завершено', data: @json($importCompleted), backgroundColor: '#10b981' },
                    { label: 'Ошибки', data: @json($importFailed), backgroundColor: '#ef4444' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        // Profile Activity Horizontal Bar
        new Chart(document.getElementById('profileChart'), {
            type: 'bar',
            data: {
                labels: @json($profileNames),
                datasets: [
                    { label: 'Всего', data: @json($profileTotal), backgroundColor: '#3b82f6' },
                    { label: 'Новых', data: @json($profileNew), backgroundColor: '#10b981' }
                ]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true } } }
        });

        // New Items Line
        new Chart(document.getElementById('newItemsChart'), {
            type: 'line',
            data: {
                labels: @json($newItemDates),
                datasets: [{
                    label: 'Новых товаров',
                    data: @json($newItemCounts),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        // Price Drops Line
        new Chart(document.getElementById('priceDropsChart'), {
            type: 'line',
            data: {
                labels: @json($priceDropDates),
                datasets: [{
                    label: 'Изменений цен',
                    data: @json($priceDropCounts),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    })();
    </script>
                </div>
            </div>
        </main>
    </body>
</html>
