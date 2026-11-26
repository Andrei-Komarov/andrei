<x-admin-layout title="Задержки eBay API">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Задержки eBay API</h1>
            <p class="text-sm text-slate-500">Настройте задержки между запросами к eBay API для предотвращения ограничений (rate limiting).</p>
        </header>
        <form method="POST" action="{{ route('admin.ebay.delays.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="profile_delay" class="block text-sm font-medium">Задержка между профилями (секунды)</label>
                <input
                    id="profile_delay"
                    name="ebay_profile_delay_seconds"
                    type="number"
                    min="0"
                    max="300"
                    step="1"
                    value="{{ old('ebay_profile_delay_seconds', $siteSettings->ebay_profile_delay_seconds ?? 120) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Пауза между запуском импорта разных профилей по расписанию. По умолчанию: 120 сек. Максимум: 300 сек (5 мин).</p>
            </div>

            <div>
                <label for="page_delay" class="block text-sm font-medium">Задержка между страницами (секунды)</label>
                <input
                    id="page_delay"
                    name="ebay_page_delay_seconds"
                    type="number"
                    min="0"
                    max="60"
                    step="1"
                    value="{{ old('ebay_page_delay_seconds', $siteSettings->ebay_page_delay_seconds ?? 15) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Пауза между запросами страниц товаров внутри одного профиля. По умолчанию: 15 сек. Максимум: 60 сек (1 мин).</p>
            </div>

            <div>
                <label for="filter_delay" class="block text-sm font-medium">Задержка при проверке фильтров (секунды)</label>
                <input
                    id="filter_delay"
                    name="ebay_filter_check_delay_seconds"
                    type="number"
                    min="0"
                    max="60"
                    step="1"
                    value="{{ old('ebay_filter_check_delay_seconds', $siteSettings->ebay_filter_check_delay_seconds ?? 20) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Пауза между запросами при проверке фильтров профиля. По умолчанию: 20 сек. Максимум: 60 сек (1 мин).</p>
            </div>

            @error('ebay_profile_delay_seconds')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_page_delay_seconds')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_filter_check_delay_seconds')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить задержки</button>
        </form>
    </section>
</x-admin-layout>
