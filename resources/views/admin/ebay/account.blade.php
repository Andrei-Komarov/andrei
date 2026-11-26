<x-admin-layout title="Настройки eBay аккаунта">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Настройки eBay аккаунта</h1>
            <p class="text-sm text-slate-500">Учетные данные для подключения к eBay API.</p>
        </header>
        <form method="POST" action="{{ route('admin.ebay.account.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <label for="ebay_client_id" class="block text-sm font-medium">Client ID</label>
                <input
                    id="ebay_client_id"
                    name="ebay_client_id"
                    type="text"
                    maxlength="255"
                    value="{{ old('ebay_client_id', $siteSettings->ebay_client_id ?? '') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                >
                <p class="mt-1 text-xs text-slate-500">Client ID из eBay Developer Console.</p>
            </div>

            <div>
                <label for="ebay_client_secret" class="block text-sm font-medium">Client Secret</label>
                <input
                    id="ebay_client_secret"
                    name="ebay_client_secret"
                    type="password"
                    maxlength="255"
                    value="{{ old('ebay_client_secret', $siteSettings->ebay_client_secret ?? '') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                >
                <p class="mt-1 text-xs text-slate-500">Client Secret из eBay Developer Console. Оставьте пустым, если не меняете.</p>
            </div>

            <div>
                <label for="ebay_marketplace_id" class="block text-sm font-medium">Marketplace ID</label>
                <input
                    id="ebay_marketplace_id"
                    name="ebay_marketplace_id"
                    type="text"
                    maxlength="50"
                    value="{{ old('ebay_marketplace_id', $siteSettings->ebay_marketplace_id ?? 'EBAY_US') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Идентификатор маркетплейса eBay (например, EBAY_US, EBAY_GB, EBAY_DE).</p>
            </div>

            <div>
                <label for="ebay_api_base_url" class="block text-sm font-medium">API Base URL</label>
                <input
                    id="ebay_api_base_url"
                    name="ebay_api_base_url"
                    type="url"
                    maxlength="255"
                    value="{{ old('ebay_api_base_url', $siteSettings->ebay_api_base_url ?? 'https://api.ebay.com') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Базовый URL eBay API для запросов данных.</p>
            </div>

            <div>
                <label for="ebay_identity_base_url" class="block text-sm font-medium">Identity Base URL</label>
                <input
                    id="ebay_identity_base_url"
                    name="ebay_identity_base_url"
                    type="url"
                    maxlength="255"
                    value="{{ old('ebay_identity_base_url', $siteSettings->ebay_identity_base_url ?? 'https://api.ebay.com') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">URL для OAuth авторизации eBay.</p>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="ebay_sandbox_mode"
                        value="1"
                        @checked(old('ebay_sandbox_mode', $siteSettings->ebay_sandbox_mode ?? false))
                        class="rounded border"
                    >
                    <span class="text-sm font-medium">Sandbox режим (тестовый)</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">Включите для работы с тестовым eBay API. Не забудьте также изменить URL на sandbox.</p>
            </div>

            @error('ebay_client_id')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_client_secret')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_marketplace_id')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_api_base_url')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_identity_base_url')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_sandbox_mode')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки eBay</button>
        </form>
    </section>
</x-admin-layout>
