<x-admin-layout title="Расширенные SEO-настройки">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Расширенные SEO-настройки</h1>
            <p class="text-sm text-slate-500">Canonical URL, Open Graph и Twitter Cards для социальных сетей и поисковых систем.</p>
        </header>

        <form method="POST" action="{{ route('admin.seo.advanced.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Canonical URL -->
            <div>
                <label for="seo_canonical_url" class="block text-sm font-medium">Canonical URL</label>
                <input
                    id="seo_canonical_url"
                    name="seo_canonical_url"
                    type="url"
                    maxlength="2048"
                    value="{{ old('seo_canonical_url', $siteSettings->seo_canonical_url ?? '') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Канонический URL сайта для предотвращения дублирования контента в поисковых системах.</p>
            </div>

            <hr class="border-slate-200">

            <!-- Open Graph -->
            <div>
                <h2 class="text-lg font-semibold">Open Graph</h2>
                <p class="text-xs text-slate-500">Мета-теги для отображения ссылки в социальных сетях (Facebook, VK, Telegram).</p>
            </div>

            <!-- OG Title -->
            <div>
                <label for="seo_og_title" class="block text-sm font-medium">Заголовок</label>
                <input
                    id="seo_og_title"
                    name="seo_og_title"
                    type="text"
                    maxlength="255"
                    value="{{ old('seo_og_title', $siteSettings->seo_og_title ?? '') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Заголовок, отображаемый при шаринге ссылки в социальных сетях.</p>
            </div>

            <!-- OG Description -->
            <div>
                <label for="seo_og_description" class="block text-sm font-medium">Описание</label>
                <textarea
                    id="seo_og_description"
                    name="seo_og_description"
                    rows="3"
                    maxlength="255"
                    class="mt-1 w-full rounded border p-2"
                >{{ old('seo_og_description', $siteSettings->seo_og_description ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Описание, отображаемое при шаринге ссылки в социальных сетях.</p>
            </div>

            <!-- OG Image -->
            <div>
                <label for="seo_og_image" class="block text-sm font-medium">Изображение (URL)</label>
                <input
                    id="seo_og_image"
                    name="seo_og_image"
                    type="url"
                    maxlength="2048"
                    value="{{ old('seo_og_image', $siteSettings->seo_og_image ?? '') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">URL изображения для превью ссылки (рекомендуется 1200×630 px).</p>
            </div>

            <!-- OG URL -->
            <div>
                <label for="seo_og_url" class="block text-sm font-medium">URL страницы</label>
                <input
                    id="seo_og_url"
                    name="seo_og_url"
                    type="url"
                    maxlength="2048"
                    value="{{ old('seo_og_url', $siteSettings->seo_og_url ?? '') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">URL страницы для Open Graph (обычно совпадает с canonical URL).</p>
            </div>

            <hr class="border-slate-200">

            <!-- Twitter Cards -->
            <div>
                <h2 class="text-lg font-semibold">Twitter Cards</h2>
                <p class="text-xs text-slate-500">Мета-теги для отображения ссылки в Twitter/X.</p>
            </div>

            <!-- Twitter Title -->
            <div>
                <label for="seo_twitter_title" class="block text-sm font-medium">Заголовок</label>
                <input
                    id="seo_twitter_title"
                    name="seo_twitter_title"
                    type="text"
                    maxlength="255"
                    value="{{ old('seo_twitter_title', $siteSettings->seo_twitter_title ?? '') }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                >
                <p class="mt-1 text-xs text-slate-500">Заголовок для Twitter Card.</p>
            </div>

            <!-- Twitter Description -->
            <div>
                <label for="seo_twitter_description" class="block text-sm font-medium">Описание</label>
                <textarea
                    id="seo_twitter_description"
                    name="seo_twitter_description"
                    rows="3"
                    maxlength="255"
                    class="mt-1 w-full rounded border p-2"
                >{{ old('seo_twitter_description', $siteSettings->seo_twitter_description ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Описание для Twitter Card.</p>
            </div>

            <!-- Twitter Image -->
            <div>
                <label for="seo_twitter_image" class="block text-sm font-medium">Изображение (URL)</label>
                <input
                    id="seo_twitter_image"
                    name="seo_twitter_image"
                    type="url"
                    maxlength="2048"
                    value="{{ old('seo_twitter_image', $siteSettings->seo_twitter_image ?? '') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">URL изображения для Twitter Card (рекомендуется 1200×675 px).</p>
            </div>

            @error('seo_canonical_url')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_og_title')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_og_description')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_og_image')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_og_url')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_twitter_title')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_twitter_description')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('seo_twitter_image')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить расширенные SEO-настройки</button>
        </form>
    </section>
</x-admin-layout>
