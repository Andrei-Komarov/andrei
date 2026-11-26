<x-admin-layout title="SEO-настройки">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">SEO-настройки</h1>
            <p class="text-sm text-slate-500">Название сайта, описание и ключевые слова для поисковых систем.</p>
        </header>

        <form method="POST" action="{{ route('admin.seo.seo.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <label for="seo_site_name" class="block text-sm font-medium">Название сайта</label>
                <input
                    id="seo_site_name"
                    name="seo_site_name"
                    type="text"
                    maxlength="255"
                    value="{{ old('seo_site_name', $siteSettings->seo_site_name ?? 'Finder') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Название сайта, отображаемое в заголовке браузера и поисковых системах.</p>
            </div>

            <div>
                <label for="seo_site_description" class="block text-sm font-medium">Описание сайта</label>
                <input
                    id="seo_site_description"
                    name="seo_site_description"
                    type="text"
                    maxlength="255"
                    value="{{ old('seo_site_description', $siteSettings->seo_site_description ?? 'Поиск товаров на eBay') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Описание сайта для мета-тега description (отображается в результатах поиска).</p>
            </div>

            <div>
                <label for="seo_keywords" class="block text-sm font-medium">Ключевые слова</label>
                <input
                    id="seo_keywords"
                    name="seo_keywords"
                    type="text"
                    maxlength="255"
                    value="{{ old('seo_keywords', $siteSettings->seo_keywords ?? 'eBay, товары, поиск, ноутбуки') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Ключевые слова через запятую для мета-тега keywords.</p>
            </div>

            @error('seo_site_name')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_site_description')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_keywords')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить SEO-настройки</button>
        </form>
    </section>
</x-admin-layout>
