<x-admin-layout title="Расширенные настройки eBay">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Расширенные настройки eBay</h1>
            <p class="text-sm text-slate-500">Retry-политика, кэш токена и таймаут запросов к eBay API.</p>
        </header>
        <form method="POST" action="{{ route('admin.ebay.advanced.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <label class="flex items-center gap-2">
                    <input
                        id="ebay_enable_retry"
                        name="ebay_enable_retry"
                        type="checkbox"
                        value="1"
                        @checked(old('ebay_enable_retry', $siteSettings->ebay_enable_retry ?? true))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Включить автоматические повторные попытки</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">При ошибке 429 (Too many requests) система автоматически повторит запрос с задержкой.</p>
            </div>

            <hr class="border-slate-200">

            <div>
                <label for="ebay_max_retries" class="block text-sm font-medium">Максимальное количество попыток</label>
                <input
                    id="ebay_max_retries"
                    name="ebay_max_retries"
                    type="number"
                    min="0"
                    max="20"
                    value="{{ old('ebay_max_retries', $siteSettings->ebay_max_retries ?? 8) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Сколько раз повторить запрос при ошибке 429. По умолчанию: 8.</p>
            </div>

            <div>
                <label for="ebay_base_delay_seconds" class="block text-sm font-medium">Базовая задержка перед повтором (секунды)</label>
                <input
                    id="ebay_base_delay_seconds"
                    name="ebay_base_delay_seconds"
                    type="number"
                    min="1"
                    max="300"
                    value="{{ old('ebay_base_delay_seconds', $siteSettings->ebay_base_delay_seconds ?? 30) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Задержка перед первой повторной попыткой с экспоненциальным нарастанием (30s → 60s → 120s → 240s...). По умолчанию: 30.</p>
            </div>

            <hr class="border-slate-200">

            <div>
                <label for="ebay_token_cache_minutes" class="block text-sm font-medium">Время жизни кэша токена (минуты)</label>
                <input
                    id="ebay_token_cache_minutes"
                    name="ebay_token_cache_minutes"
                    type="number"
                    min="10"
                    max="1440"
                    value="{{ old('ebay_token_cache_minutes', $siteSettings->ebay_token_cache_minutes ?? 110) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Сколько минут кэшировать OAuth-токен eBay перед обновлением. По умолчанию: 110.</p>
            </div>

            <div>
                <label for="ebay_request_timeout" class="block text-sm font-medium">Таймаут HTTP-запроса (секунды)</label>
                <input
                    id="ebay_request_timeout"
                    name="ebay_request_timeout"
                    type="number"
                    min="5"
                    max="120"
                    value="{{ old('ebay_request_timeout', $siteSettings->ebay_request_timeout ?? 30) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Максимальное время ожидания ответа от eBay API. По умолчанию: 30.</p>
            </div>

            @error('ebay_max_retries')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_base_delay_seconds')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_token_cache_minutes')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_request_timeout')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить расширенные настройки</button>
        </form>
    </section>
</x-admin-layout>
