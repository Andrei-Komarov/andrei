<x-admin-layout title="Дополнительные настройки eBay">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Дополнительные настройки eBay</h1>
            <p class="text-sm text-slate-500">Категория, сортировка, фильтры и игнорирование продавцов при импорте с eBay.</p>
        </header>
        <form method="POST" action="{{ route('admin.ebay.extra.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <label for="ebay_category_id" class="block text-sm font-medium">Категория eBay</label>
                <select
                    id="ebay_category_id"
                    name="ebay_category_id"
                    class="mt-1 w-full rounded border p-2"
                >
                    <option value="175672" @selected(old('ebay_category_id', $siteSettings->ebay_category_id ?? '175672') === '175672')>Laptops & Netbooks (175672)</option>
                    <option value="171215" @selected(old('ebay_category_id', $siteSettings->ebay_category_id ?? '175672') === '171215')>Computers/ Tablets (171215)</option>
                    <option value="0" @selected(old('ebay_category_id', $siteSettings->ebay_category_id ?? '175672') === '0')>Все категории</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Категория eBay для поиска товаров. «Все категории» — поиск по всему каталогу.</p>
            </div>

            <div>
                <label for="ebay_sort_by" class="block text-sm font-medium">Сортировка результатов</label>
                <select
                    id="ebay_sort_by"
                    name="ebay_sort_by"
                    class="mt-1 w-full rounded border p-2"
                >
                    <option value="price" @selected(old('ebay_sort_by', $siteSettings->ebay_sort_by ?? 'price') === 'price')>Цена (по возрастанию)</option>
                    <option value="RelevancePlus" @selected(old('ebay_sort_by', $siteSettings->ebay_sort_by ?? 'price') === 'RelevancePlus')>Релевантность</option>
                    <option value="EndTimeSoon" @selected(old('ebay_sort_by', $siteSettings->ebay_sort_by ?? 'price') === 'EndTimeSoon')>Скоро завершение</option>
                    <option value="PricePlusShippingBuyerHi" @selected(old('ebay_sort_by', $siteSettings->ebay_sort_by ?? 'price') === 'PricePlusShippingBuyerHi')>Цена (по убыванию)</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Порядок сортировки результатов поиска на eBay.</p>
            </div>

            <div>
                <label for="ebay_ignored_sellers" class="block text-sm font-medium">Игнорируемые продавцы</label>
                <input
                    id="ebay_ignored_sellers"
                    name="ebay_ignored_sellers"
                    type="text"
                    value="{{ old('ebay_ignored_sellers', is_array($siteSettings->ebay_ignored_sellers) ? implode(', ', $siteSettings->ebay_ignored_sellers) : ($siteSettings->ebay_ignored_sellers ?? '')) }}"
                    placeholder="Seller1, Seller2, Seller3"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Список продавцов для игнорирования при импорте. Укажите имена через запятую.</p>
            </div>

            <div>
                <label for="ebay_seller_country" class="block text-sm font-medium">Страна продавца</label>
                <select
                    id="ebay_seller_country"
                    name="ebay_seller_country"
                    class="mt-1 w-full rounded border p-2"
                >
                    <option value="" @selected(!old('ebay_seller_country', $siteSettings->ebay_seller_country ?? ''))>Любая страна</option>
                    <option value="US" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'US')>США</option>
                    <option value="GB" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'GB')>Великобритания</option>
                    <option value="DE" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'DE')>Германия</option>
                    <option value="FR" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'FR')>Франция</option>
                    <option value="CN" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'CN')>Китай</option>
                    <option value="JP" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'JP')>Япония</option>
                    <option value="HK" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'HK')>Гонконг</option>
                    <option value="AU" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'AU')>Австралия</option>
                    <option value="CA" @selected(old('ebay_seller_country', $siteSettings->ebay_seller_country ?? '') === 'CA')>Канада</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Фильтр по стране продавца. «Любая страна» — без фильтрации.</p>
            </div>

            <hr class="border-slate-200">

            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input
                        id="ebay_free_shipping_only"
                        name="ebay_free_shipping_only"
                        type="checkbox"
                        value="1"
                        @checked(old('ebay_free_shipping_only', $siteSettings->ebay_free_shipping_only ?? false))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Только бесплатная доставка</span>
                </label>
                <p class="ml-6 text-xs text-slate-500">Показывать только товары с бесплатной доставкой.</p>

                <label class="flex items-center gap-2">
                    <input
                        id="ebay_buy_it_now_only"
                        name="ebay_buy_it_now_only"
                        type="checkbox"
                        value="1"
                        @checked(old('ebay_buy_it_now_only', $siteSettings->ebay_buy_it_now_only ?? false))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Только Buy It Now</span>
                </label>
                <p class="ml-6 text-xs text-slate-500">Показывать только товары с мгновенной покупкой (без аукционов).</p>

                <label class="flex items-center gap-2">
                    <input
                        id="ebay_auction_only"
                        name="ebay_auction_only"
                        type="checkbox"
                        value="1"
                        @checked(old('ebay_auction_only', $siteSettings->ebay_auction_only ?? false))
                        class="h-4 w-4 rounded border-gray-300 text-blue-700"
                    >
                    <span class="text-sm font-medium">Только аукционы</span>
                </label>
                <p class="ml-6 text-xs text-slate-500">Показывать только аукционные лоты.</p>
            </div>

            @error('ebay_category_id')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('ebay_sort_by')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки eBay</button>
        </form>
    </section>
</x-admin-layout>
