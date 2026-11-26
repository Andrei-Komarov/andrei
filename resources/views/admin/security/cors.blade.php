<x-admin-layout title="CORS и безопасность API">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">CORS и безопасность API</h1>
            <p class="text-sm text-slate-500">Настройки кросс-доменного доступа и ограничения запросов к API.</p>
        </header>

        <form method="POST" action="{{ route('admin.security.cors.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- CORS Allowed Origins -->
            <div>
                <label for="cors_allowed_origins" class="block text-sm font-medium">CORS Allowed Origins</label>
                <input
                    id="cors_allowed_origins"
                    name="cors_allowed_origins"
                    type="text"
                    value="{{ old('cors_allowed_origins', $siteSettings->cors_allowed_origins ?? '*') }}"
                    placeholder="*, https://example.com, https://app.example.com"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Список доменов, которым разрешён доступ к API. «*» — разрешить все домены. Укажите через запятую.</p>
            </div>

            <!-- CORS Allowed Methods -->
            <div>
                <label for="cors_allowed_methods" class="block text-sm font-medium">CORS Allowed Methods</label>
                <input
                    id="cors_allowed_methods"
                    name="cors_allowed_methods"
                    type="text"
                    value="{{ old('cors_allowed_methods', $siteSettings->cors_allowed_methods ?? 'GET,POST,PUT,PATCH,DELETE') }}"
                    placeholder="GET,POST,PUT,PATCH,DELETE"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Разрешённые HTTP-методы для кросс-доменных запросов. Укажите через запятую.</p>
            </div>

            <!-- CORS Allowed Headers -->
            <div>
                <label for="cors_allowed_headers" class="block text-sm font-medium">CORS Allowed Headers</label>
                <input
                    id="cors_allowed_headers"
                    name="cors_allowed_headers"
                    type="text"
                    value="{{ old('cors_allowed_headers', $siteSettings->cors_allowed_headers ?? 'Content-Type,Authorization') }}"
                    placeholder="Content-Type,Authorization,X-Custom-Header"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Разрешённые заголовки в кросс-доменных запросах. Укажите через запятую.</p>
            </div>

            <!-- CORS Allow Credentials -->
            <div class="flex items-center gap-2">
                <input
                    id="cors_allow_credentials"
                    name="cors_allow_credentials"
                    type="checkbox"
                    value="1"
                    @checked(old('cors_allow_credentials', $siteSettings->cors_allow_credentials ?? false))
                    class="h-4 w-4 rounded border-gray-300 text-blue-700"
                >
                <label for="cors_allow_credentials" class="text-sm font-medium">Разрешить отправку куки и авторизации</label>
            </div>
            <p class="text-xs text-slate-500">Включить отправку куки и заголовков авторизации в кросс-доменных запросах.</p>

            <hr class="border-slate-200">

            <!-- API Rate Limit -->
            <div>
                <label for="api_rate_limit" class="block text-sm font-medium">Лимит запросов к API</label>
                <input
                    id="api_rate_limit"
                    name="api_rate_limit"
                    type="number"
                    min="1"
                    max="10000"
                    value="{{ old('api_rate_limit', $siteSettings->api_rate_limit ?? 60) }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Максимальное количество запросов к API за указанный временной интервал.</p>
            </div>

            <!-- API Rate Limit Window -->
            <div>
                <label for="api_rate_limit_window" class="block text-sm font-medium">Временное окно (минуты)</label>
                <input
                    id="api_rate_limit_window"
                    name="api_rate_limit_window"
                    type="number"
                    min="1"
                    max="1440"
                    value="{{ old('api_rate_limit_window', $siteSettings->api_rate_limit_window ?? 60) }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Временной интервал в минутах для ограничения запросов. 60 минут = 1 час.</p>
            </div>

            @error('cors_allowed_origins')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('api_rate_limit')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки CORS</button>
        </form>
    </section>
</x-admin-layout>
