<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Новые товары — eBay Finder</title>
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

                <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-4">
                        <div>
                            <h1 class="text-2xl font-bold">Новые товары</h1>
                            <p class="text-sm text-slate-500">Объявления, найденные вашими профилями поиска</p>
                        </div>
                        @if ($notifications->total() > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Отметить все прочитанными
                                </button>
                            </form>
                        @endif
                    </header>
                    <div class="divide-y">
                        @forelse ($notifications as $notification)
                            <article class="flex flex-wrap items-center justify-between gap-4 p-4" data-notification-list-item>
                                <div class="min-w-0">
                                    <a href="{{ $notification->data['item_url'] }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 hover:underline">
                                        {{ $notification->data['title'] }}
                                    </a>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Профиль: {{ $notification->data['profile_name'] }} ·
                                        {{ $notification->data['currency'] }} {{ number_format((float) $notification->data['total_price'], 2) }}
                                    </p>
                                    @if (($notification->data['smart_alert_type'] ?? null) === 'profile_match')
                                        <p class="mt-1 text-xs font-medium text-violet-700">
                                            Известный лот найден в новом профиле. Совпадения: {{ implode(', ', $notification->data['profile_names'] ?? []) }}
                                        </p>
                                    @elseif (($notification->data['smart_alert_type'] ?? null) === 'shared_price_change')
                                        <p class="mt-1 text-xs font-medium text-emerald-700">
                                            Цена изменилась в нескольких профилях:
                                            {{ $notification->data['previous_currency'] }} {{ number_format((float) $notification->data['previous_price'], 2) }}
                                            → {{ $notification->data['currency'] }} {{ number_format((float) $notification->data['total_price'], 2) }}.
                                            Профили: {{ implode(', ', $notification->data['profile_names'] ?? []) }}
                                        </p>
                                    @endif
                                    @if (($duplicateProfilesByListingId[(int) ($notification->data['listing_id'] ?? 0)] ?? []) !== [])
                                        <p class="mt-1 text-xs font-medium text-violet-700">
                                            Также найдено в профилях: {{ implode(', ', $duplicateProfilesByListingId[(int) $notification->data['listing_id']]) }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->format('d.m.Y H:i') }}</p>
                                </div>
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded border px-3 py-2 text-sm hover:bg-slate-50">Отметить прочитанным</button>
                                </form>
                            </article>
                        @empty
                            <p class="p-8 text-center text-slate-500">Новых товаров пока нет. Когда автоматический поиск найдёт объявления, они появятся здесь.</p>
                        @endforelse
                    </div>
                    <div class="p-4">{{ $notifications->links() }}</div>
                </section>
            </div>
        </main>
    </body>
</html>
