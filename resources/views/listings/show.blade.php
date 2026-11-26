<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>{{ $listing->title }} — Finder</title>
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
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-blue-700 hover:underline">← К списку товаров</a>
                            @php
                                $referrer = request()->headers->get('referer', '');
                                if (str_contains($referrer, '/listings/excluded')) {
                                    echo '<a href="'.route('listings.excluded').'">← К исключённым товарам</a>';
                                }
                            @endphp
                        </div>
                        <h1 class="mt-3 text-2xl font-bold">{{ $listing->title }}</h1>
                        <p class="mt-1 text-sm text-slate-600">
                            Профиль: {{ $listing->searchProfiles->pluck('name')->join(', ') }} · Найден: {{ $listing->created_at->format('d.m.Y H:i') }}
                        </p>
                    </header>

                    <div class="grid gap-6 p-4 md:grid-cols-2">
                        <!-- Image -->
                        <div>
                            @if ($listing->image_url)
                                <img src="{{ $listing->image_url }}" alt="{{ $listing->title }}" class="w-full rounded-lg object-contain" loading="lazy">
                            @else
                                <div class="flex h-64 items-center justify-center rounded-lg bg-slate-100 text-slate-400">Нет фото</div>
                            @endif

                            <!-- Gallery images from raw payload -->
                            @php
                                $galleryImages = $listing->galleryImageUrls();
                            @endphp
                            @if (count($galleryImages) > 1)
                                <div class="mt-3 flex gap-2 overflow-x-auto">
                                    @foreach (array_slice($galleryImages, 1) as $imgUrl)
                                        <img src="{{ $imgUrl }}" alt="" class="h-16 w-16 rounded object-cover" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="space-y-4">
                            <!-- Price -->
                            <div>
                                <p class="text-sm text-slate-500">Цена с доставкой</p>
                                <p class="text-2xl font-bold">{{ $listing->currency }} {{ number_format((float) $listing->total_price, 2) }}</p>
                                <p class="text-sm text-slate-500">
                                    Товар: {{ $listing->currency }} {{ number_format((float) $listing->price, 2) }}
                                    @if ($listing->shipping_price)
                                        + доставка {{ $listing->currency }} {{ number_format((float) $listing->shipping_price, 2) }}
                                    @endif
                                </p>
                            </div>

                            <!-- Links -->
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ $listing->item_url }}" target="_blank" rel="noopener noreferrer" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Открыть на eBay</a>
                                <a href="{{ route('listings.price-history', $listing) }}" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">История цены</a>
                            </div>

                            <!-- Seller -->
                            <div>
                                <p class="text-sm font-medium text-slate-500">Продавец</p>
                                <p class="text-sm">{{ $listing->seller ?? '—' }}</p>
                                @if ($listing->seller_feedback)
                                    <p class="text-xs text-slate-500">Отзывы: {{ number_format((float) $listing->seller_feedback, 1) }}</p>
                                @endif
                                @if ($listing->seller_description)
                                    <p class="mt-1 text-xs text-slate-600">{{ Str::limit($listing->seller_description, 200) }}</p>
                                @endif
                            </div>

                            <!-- Condition -->
                            @if ($listing->condition)
                                <div>
                                    <p class="text-sm font-medium text-slate-500">Состояние</p>
                                    <p class="text-sm">{{ $listing->condition }}</p>
                                </div>
                            @endif

                            <!-- Specifications -->
                            @php
                                $specs = [];
                                if ($listing->cpu) $specs['Процессор'] = $listing->cpu;
                                if ($listing->ram_gb) $specs['ОЗУ'] = $listing->ram_gb.' ГБ';
                                if ($listing->storage) $specs['Накопитель'] = $listing->storage;
                                if ($listing->screen) $specs['Экран'] = $listing->screen;
                                if ($listing->gpu) $specs['Видеокарта'] = $listing->gpu;
                            @endphp
                            @if (count($specs))
                                <div>
                                    <p class="text-sm font-medium text-slate-500">Характеристики</p>
                                    <dl class="mt-1 grid grid-cols-2 gap-1 text-sm">
                                        @foreach ($specs as $label => $value)
                                            <dt class="text-slate-500">{{ $label }}</dt>
                                            <dd>{{ $value }}</dd>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif

                            <!-- Exclusion info -->
                            @if ($listing->excluded)
                                <div class="rounded-lg bg-red-50 p-3">
                                    <p class="text-sm font-medium text-red-800">Товар исключён</p>
                                    @if ($listing->excluded_reason)
                                        <p class="text-xs text-red-700">Причина: {{ $listing->excluded_reason }}</p>
                                    @endif
                                </div>
                            @endif

                            <!-- Status -->
                            @if ($listing->status)
                                <div>
                                    <p class="text-sm font-medium text-slate-500">Статус</p>
                                    <p class="text-sm">{{ ucfirst($listing->status) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
