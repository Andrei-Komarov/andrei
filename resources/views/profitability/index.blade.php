<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Рентабельность — Finder</title>
        @include('partials.theme-bootstrap')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('partials.sitebar')

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            @include('partials.sidebar')

            <div class="min-w-0 space-y-4">
                @if (session('success'))
                    <div class="rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="rounded bg-red-100 p-3 text-red-800">
                        <p class="font-medium">Проверьте заполненные поля.</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="rounded-xl bg-white p-4 shadow-sm">
                    <header class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">Расчёт рентабельности</h1>
                            <p class="text-sm text-slate-500">Укажите затраты на покупку и продажу в {{ $currencyLabel }}. Цена выбранного товара показывается в исходной валюте eBay.</p>
                            <p class="mt-1 text-xs text-slate-500">Чистая прибыль = продажа − покупка − доставка по США − реклама − растаможка − запчасти. ROI рассчитывается от общих затрат, маржа — от цены продажи.</p>
                        </div>
                        <div data-profitability-preview class="hidden rounded-lg border border-slate-200 bg-slate-50 p-3 text-right">
                            <div class="text-xs text-slate-500">Всего затрат</div>
                            <div data-preview-total-cost class="mb-1 font-medium text-slate-900">0.00 {{ $currencyLabel }}</div>
                            <div class="text-xs text-slate-500">Чистая прибыль</div>
                            <div data-preview-net-profit class="mb-1 font-medium">0.00 {{ $currencyLabel }}</div>
                            <div class="text-xs text-slate-500">ROI / Маржа</div>
                            <div data-preview-roi-margin class="font-medium text-slate-900">0.00% / 0.00%</div>
                        </div>
                    </header>
                    <div class="mb-4"></div>

                    @if ($listings->isEmpty())
                        <p class="rounded bg-amber-50 p-3 text-sm text-amber-800">В ваших профилях пока нет товаров для расчёта.</p>
                    @else
                        <form method="POST" action="{{ route('profitability.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-profitability-form data-currency="{{ $currencyCode }}" data-kgs-per-usd="{{ $kgsPerUsd }}">
                            @csrf
                            <label for="listing_id" class="text-sm font-medium sm:col-span-2 lg:col-span-3">
                                Товар из списка eBay
                                <select id="listing_id" name="listing_id" required class="mt-1 w-full rounded border border-slate-300 bg-white p-2 font-normal">
                                    <option value="">Выберите товар</option>
                                    @foreach ($listings as $listing)
                                        @php($listingPrice = $listing->total_price ?? $listing->price)
                                        <option
                                            value="{{ $listing->id }}"
                                            data-price="{{ number_format((float) $listingPrice, 2, '.', '') }}"
                                            data-shipping-price="{{ number_format((float) ($listing->shipping_price ?? 0), 2, '.', '') }}"
                                            data-currency="{{ $listing->currency }}"
                                            data-item-url="{{ $listing->item_url }}"
                                            @selected((string) old('listing_id') === (string) $listing->id)
                                        >
                                            {{ $listing->title }} — {{ number_format((float) ($listing->total_price ?? $listing->price), 2, '.', ' ') }} {{ $listing->currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="hidden sm:col-span-2 lg:col-span-3" data-ebay-link-container>
                                <a href="#" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-700 hover:underline" data-ebay-item-link></a>
                            </div>

                            <label for="purchase_price" class="text-sm font-medium">
                                Цена покупки, {{ $currencyLabel }}
                                <input id="purchase_price" name="purchase_price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('purchase_price') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                                <span data-purchase-price-notice aria-live="polite" class="mt-1 block text-xs font-normal text-slate-500">При выборе товара цена и доставка подставятся автоматически. USD и KGS пересчитываются по курсу из настроек.</span>
                            </label>
                            <label for="additional_costs" class="text-sm font-medium">
                                Доставка по США, {{ $currencyLabel }}
                                <input id="additional_costs" name="additional_costs" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('additional_costs', '0.00') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="advertising_costs" class="text-sm font-medium">
                                Затраты на рекламу, {{ $currencyLabel }}
                                <input id="advertising_costs" name="advertising_costs" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('advertising_costs', '0.00') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="customs_cost" class="text-sm font-medium">
                                Стоимость растаможки, {{ $currencyLabel }}
                                <input id="customs_cost" name="customs_cost" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('customs_cost', '0.00') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="sale_price" class="text-sm font-medium">
                                Цена продажи, {{ $currencyLabel }}
                                <input id="sale_price" name="sale_price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('sale_price') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            @php($selectedPartIds = old('part_ids', ['']))
                            <fieldset class="space-y-2 sm:col-span-2 lg:col-span-3" data-part-selector data-max-parts="30">
                                <legend class="text-sm font-medium">Запчасти</legend>
                                <p class="text-xs text-slate-500">Можно добавить несколько запчастей. Цены запчастей заданы администратором в сомах и пересчитываются в выбранную валюту.</p>
                                <div class="space-y-2" data-part-rows>
                                    @foreach ($selectedPartIds as $selectedPartId)
                                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]" data-part-row>
                                            <select name="part_ids[]" aria-label="Запчасть" class="w-full rounded border border-slate-300 bg-white p-2">
                                                <option value="">Не выбрана</option>
                                                @foreach ($parts as $part)
                                                    @php($displayPrice = $currencyCode === 'USD' ? (float) $part->price / (float) $kgsPerUsd : (float) $part->price)
                                                    <option value="{{ $part->id }}" @selected((string) $selectedPartId === (string) $part->id)>{{ $part->name }} — {{ number_format($displayPrice, 2, '.', ' ') }} {{ $currencyLabel }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="text-sm text-red-700 hover:underline" data-remove-part @disabled(count($selectedPartIds) === 1)>Убрать</button>
                                        </div>
                                    @endforeach
                                </div>
                                <div>
                                    <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" data-add-part>Добавить запчасть</button>
                                </div>
                                <template data-part-row-template>
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]" data-part-row>
                                        <select name="part_ids[]" aria-label="Запчасть" class="w-full rounded border border-slate-300 bg-white p-2">
                                            <option value="">Не выбрана</option>
                                            @foreach ($parts as $part)
                                                @php($displayPrice = $currencyCode === 'USD' ? (float) $part->price / (float) $kgsPerUsd : (float) $part->price)
                                                <option value="{{ $part->id }}">{{ $part->name }} — {{ number_format($displayPrice, 2, '.', ' ') }} {{ $currencyLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="text-sm text-red-700 hover:underline" data-remove-part>Убрать</button>
                                    </div>
                                </template>
                            </fieldset>
                            <div class="flex items-end sm:col-span-2 lg:col-span-2">
                                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Рассчитать и сохранить</button>
                            </div>
                        </form>
                    @endif
                </section>

                <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <header class="flex items-center justify-between p-4">
                        <h2 class="text-xl font-bold">История расчётов</h2>
                        @include('partials.table-column-settings', ['tableKey' => 'profitability', 'containerClass' => 'p-0'])
                    </header>
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'product', 'sortKey' => 'product', 'label' => 'Товар', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'purchase', 'sortKey' => 'purchase_price', 'label' => 'Покупка', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'costs', 'sortKey' => 'additional_costs', 'label' => 'Доставка по США', 'sort' => $sort])
                                    <th class="p-3" data-column-key="parts">Запчасти</th>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'advertising', 'sortKey' => 'advertising_costs', 'label' => 'Реклама', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'customs', 'sortKey' => 'customs_cost', 'label' => 'Растаможка', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'total_cost', 'sortKey' => 'total_cost', 'label' => 'Всего затрат', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'sale', 'sortKey' => 'sale_price', 'label' => 'Продажа', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'commission', 'sortKey' => 'commission_amount', 'label' => 'Комиссия (старые расчёты)', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'profit', 'sortKey' => 'net_profit', 'label' => 'Чистая прибыль', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'roi', 'sortKey' => 'roi_percentage', 'label' => 'ROI', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'margin', 'sortKey' => 'profit_margin_percentage', 'label' => 'Маржа', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'created_at', 'label' => 'Дата', 'sort' => $sort])
                                    <th class="p-3" data-column-key="actions">Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($calculations as $calculation)
                                    @php($currencyLabel = $calculation->currency_code === 'KGS' ? 'сом' : $calculation->currency_code)
                                    <tr class="border-t border-slate-100">
                                        <td class="max-w-72 p-3">
                                            @if ($calculation->listing && $calculation->listing->item_url)
                                                <a href="{{ $calculation->listing->item_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:underline">{{ $calculation->listing_title }}</a>
                                            @else
                                                {{ $calculation->listing_title }}
                                            @endif
                                        </td>
                                        <td class="p-3">{{ number_format((float) $calculation->purchase_price, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->additional_costs, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">
                                            <div class="max-h-[7lh] overflow-y-auto">
                                                @forelse ($calculation->parts_snapshot ?? [] as $part)
                                                    <div>{{ $part['name'] }} — {{ number_format((float) $part['price'], 2, '.', ' ') }} {{ $currencyLabel }}</div>
                                                @empty
                                                    <span class="text-slate-500">—</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="p-3">{{ number_format((float) $calculation->advertising_costs, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->customs_cost, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->total_cost, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->sale_price, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->commission_amount, 2, '.', ' ') }} {{ $currencyLabel }} ({{ number_format((float) $calculation->commission_percent, 2, '.', ' ') }}%)</td>
                                        <td class="p-3 font-medium {{ (float) $calculation->net_profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $calculation->net_profit, 2, '.', ' ') }} {{ $currencyLabel }}</td>
                                        <td class="p-3">{{ number_format((float) $calculation->roi_percentage, 2, '.', ' ') }}%</td>
                                        <td class="p-3">{{ number_format((float) $calculation->profit_margin_percentage, 2, '.', ' ') }}%</td>
                                        <td class="text-center whitespace-nowrap p-3"><div>{{ $calculation->created_at->format('d.m.Y') }}</div><div class="text-xs text-slate-500">{{ $calculation->created_at->format('H:i') }}</div></td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" data-edit-calculation data-calculation-edit-url="{{ route('profitability.edit', $calculation) }}" data-calculation-update-url="{{ route('profitability.update', $calculation) }}" class="rounded border border-blue-300 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50">Редактировать</button>
                                                <form method="POST" action="{{ route('profitability.destroy', $calculation) }}" data-confirm-dialog-form data-confirm-message="Удалить этот расчёт из истории?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Удалить</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="p-6 text-center text-slate-500">Сохранённых расчётов пока нет.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $calculations->links() }}</div>
                </section>

                <dialog
                    id="edit-calculation-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="edit-calculation-title"
                    class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(48rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
                >
                    <div class="p-6">
                        <h2 id="edit-calculation-title" class="text-xl font-semibold">Редактировать расчёт</h2>
                        <p id="edit-calculation-product" class="mt-1 text-sm text-slate-500"></p>
                        <div id="edit-calculation-loading" class="mt-4 text-center text-slate-500">
                            <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <p class="mt-2">Загрузка…</p>
                        </div>
                        <form id="edit-calculation-form" class="mt-4 hidden grid gap-4 sm:grid-cols-2" data-edit-calculation-form>
                            <input type="hidden" name="_method" value="PATCH" id="edit-calculation-method">
                            <input type="hidden" id="edit-calculation-csrf" name="_token" value="{{ csrf_token() }}">

                            <label for="edit_purchase_price" class="text-sm font-medium">
                                Цена покупки
                                <input id="edit_purchase_price" name="purchase_price" type="number" min="0" max="99999999.99" step="0.01" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="edit_additional_costs" class="text-sm font-medium">
                                Доставка по США
                                <input id="edit_additional_costs" name="additional_costs" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="edit_advertising_costs" class="text-sm font-medium">
                                Затраты на рекламу
                                <input id="edit_advertising_costs" name="advertising_costs" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="edit_customs_cost" class="text-sm font-medium">
                                Стоимость растаможки
                                <input id="edit_customs_cost" name="customs_cost" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>
                            <label for="edit_sale_price" class="text-sm font-medium">
                                Цена продажи
                                <input id="edit_sale_price" name="sale_price" type="number" min="0" max="99999999.99" step="0.01" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                            </label>

                            <fieldset class="space-y-2 sm:col-span-2" data-edit-part-selector data-max-parts="30">
                                <legend class="text-sm font-medium">Запчасти</legend>
                                <div class="space-y-2" data-edit-part-rows></div>
                                <div>
                                    <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" data-edit-add-part>Добавить запчасть</button>
                                </div>
                                <template data-edit-part-row-template>
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]" data-edit-part-row>
                                        <select name="part_ids[]" aria-label="Запчасть" class="w-full rounded border border-slate-300 bg-white p-2" data-edit-part-select>
                                        </select>
                                        <button type="button" class="text-sm text-red-700 hover:underline" data-edit-remove-part>Убрать</button>
                                    </div>
                                </template>
                            </fieldset>

                            <div class="flex items-end justify-between sm:col-span-2">
                                <button type="button" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50" data-close-edit-dialog>Отмена</button>
                                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить изменения</button>
                            </div>
                        </form>
                    </div>
                </dialog>
            </div>
        </main>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Live profitability preview
            const form = document.querySelector('[data-profitability-form]');
            const previewEl = document.querySelector('[data-profitability-preview]');
            const previewTotalCost = document.querySelector('[data-preview-total-cost]');
            const previewNetProfit = document.querySelector('[data-preview-net-profit]');
            const previewRoiMargin = document.querySelector('[data-preview-roi-margin]');
            const currencyLabel = '{{ $currencyLabel }}';

            if (form && previewEl) {
                function calculatePreview() {
                    const purchasePrice = parseFloat(form.querySelector('#purchase_price')?.value) || 0;
                    const additionalCosts = parseFloat(form.querySelector('#additional_costs')?.value) || 0;
                    const advertisingCosts = parseFloat(form.querySelector('#advertising_costs')?.value) || 0;
                    const customsCost = parseFloat(form.querySelector('#customs_cost')?.value) || 0;
                    const salePrice = parseFloat(form.querySelector('#sale_price')?.value) || 0;

                    // Calculate parts total
                    let partsTotal = 0;
                    const partSelects = form.querySelectorAll('[data-part-row] select[name="part_ids[]"]');
                    partSelects.forEach(select => {
                        const option = select.options[select.selectedIndex];
                        if (option && option.value) {
                            const priceText = option.textContent.match(/—\s*([\d\s.,]+)/);
                            if (priceText) {
                                partsTotal += parseFloat(priceText[1].replace(/[\s,]/g, '')) || 0;
                            }
                        }
                    });

                    const totalCost = purchasePrice + additionalCosts + advertisingCosts + customsCost + partsTotal;
                    const netProfit = salePrice - totalCost;
                    const roi = totalCost > 0 ? (netProfit / totalCost) * 100 : 0;
                    const margin = salePrice > 0 ? (netProfit / salePrice) * 100 : 0;

                    previewTotalCost.textContent = `${totalCost.toFixed(2).replace('.', ',')} ${currencyLabel}`;
                    previewNetProfit.textContent = `${netProfit.toFixed(2).replace('.', ',')} ${currencyLabel}`;
                    previewNetProfit.className = `mb-1 font-medium ${netProfit >= 0 ? 'text-emerald-700' : 'text-red-700'}`;
                    previewRoiMargin.textContent = `${roi.toFixed(2).replace('.', ',')}% / ${margin.toFixed(2).replace('.', ',')}%`;

                    previewEl.classList.remove('hidden');
                }

                form.addEventListener('input', calculatePreview);
                form.addEventListener('change', calculatePreview);
            }

            const editDialog = document.querySelector('#edit-calculation-dialog');
            const editLoading = document.querySelector('#edit-calculation-loading');
            const editForm = document.querySelector('#edit-calculation-form');
            const editProduct = document.querySelector('#edit-calculation-product');
            const editTitle = document.querySelector('#edit-calculation-title');
            const editPartRows = document.querySelector('[data-edit-part-rows]');
            const editPartTemplate = document.querySelector('[data-edit-part-row-template]');
            const editAddPartBtn = document.querySelector('[data-edit-add-part]');
            const editCloseBtn = document.querySelector('[data-close-edit-dialog]');
            let currentEditUrl = '';
            let currentUpdateUrl = '';
            let editPartsData = [];

            // Populate part selects
            function populatePartSelects(partIds = []) {
                const selects = editPartRows.querySelectorAll('[data-edit-part-select]');
                selects.forEach(select => {
                    const currentValue = select.value;
                    select.innerHTML = '<option value="">Не выбрана</option>';
                    editPartsData.forEach(part => {
                        const displayPrice = parseFloat(part.price).toFixed(2);
                        const option = document.createElement('option');
                        option.value = part.id;
                        option.textContent = `${part.name} — ${displayPrice}`;
                        if (partIds.includes(part.id)) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                });
            }

            // Add part row
            function addEditPartRow(partId = '') {
                const row = editPartTemplate.content.cloneNode(true);
                const partRow = row.querySelector('[data-edit-part-row]');
                const select = partRow.querySelector('[data-edit-part-select]');
                populatePartSelectsForSelect(select, [partId]);
                if (partId) {
                    select.value = partId;
                }
                const removeBtn = partRow.querySelector('[data-edit-remove-part]');
                removeBtn.addEventListener('click', () => {
                    if (editPartRows.children.length > 1) {
                        partRow.remove();
                    }
                });
                editPartRows.appendChild(row);
            }

            function populatePartSelectsForSelect(select, selectedIds = []) {
                select.innerHTML = '<option value="">Не выбрана</option>';
                editPartsData.forEach(part => {
                    const displayPrice = parseFloat(part.price).toFixed(2);
                    const option = document.createElement('option');
                    option.value = part.id;
                    option.textContent = `${part.name} — ${displayPrice}`;
                    if (selectedIds.includes(part.id)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }

            editAddPartBtn?.addEventListener('click', () => addEditPartRow());

            editCloseBtn?.addEventListener('click', () => editDialog.close());

            editDialog.addEventListener('click', (event) => {
                if (event.target === editDialog) {
                    editDialog.close();
                }
            });

            editDialog.addEventListener('close', () => {
                editLoading.classList.remove('hidden');
                editForm.classList.add('hidden');
                editForm.classList.remove('grid');
            });

            // Edit button click handler
            for (const button of document.querySelectorAll('[data-edit-calculation]')) {
                button.addEventListener('click', async () => {
                    currentEditUrl = button.dataset.calculationEditUrl;
                    currentUpdateUrl = button.dataset.calculationUpdateUrl;
                    editLoading.classList.remove('hidden');
                    editForm.classList.add('hidden');
                    editForm.classList.remove('grid');
                    editDialog.showModal();

                    try {
                        const response = await fetch(currentEditUrl, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            editLoading.classList.add('hidden');
                            editLoading.innerHTML = '<p class="text-red-600">Не удалось загрузить данные.</p>';
                            return;
                        }

                        editPartsData = data.parts || [];
                        const calculation = data.calculation;

                        editProduct.textContent = calculation.listing_title;

                        document.getElementById('edit_purchase_price').value = parseFloat(calculation.purchase_price).toFixed(2);
                        document.getElementById('edit_additional_costs').value = parseFloat(calculation.additional_costs).toFixed(2);
                        document.getElementById('edit_advertising_costs').value = parseFloat(calculation.advertising_costs).toFixed(2);
                        document.getElementById('edit_customs_cost').value = parseFloat(calculation.customs_cost).toFixed(2);
                        document.getElementById('edit_sale_price').value = parseFloat(calculation.sale_price).toFixed(2);

                        // Restore parts
                        editPartRows.innerHTML = '';
                        const snapshotParts = calculation.parts_snapshot || [];
                        const snapshotPartIds = snapshotParts.map(p => p.id);
                        if (snapshotPartIds.length > 0) {
                            snapshotPartIds.forEach(partId => addEditPartRow(partId));
                        } else {
                            addEditPartRow();
                        }

                        editLoading.classList.add('hidden');
                        editForm.classList.remove('hidden');
                        editForm.classList.add('grid');
                    } catch (error) {
                        editLoading.classList.add('hidden');
                        editLoading.innerHTML = '<p class="text-red-600">Не удалось загрузить данные.</p>';
                    }
                });
            }

            // Form submission
            editForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData(editForm);

                try {
                    const response = await fetch(currentUpdateUrl, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const errorData = await response.json();
                            if (errorData.errors) {
                                const messages = Object.values(errorData.errors).flat().join('\n');
                                alert('Проверьте заполненные поля:\n' + messages);
                            } else {
                                alert('Не удалось сохранить изменения: ' + (errorData.message || ''));
                            }
                        } else {
                            const errorText = await response.text();
                            console.error('Edit error response:', errorText.substring(0, 1000));
                            const match = errorText.match(/<pre[^>]*>([\s\S]*?)<\/pre>/i);
                            const errorMsg = match ? match[1].trim().substring(0, 500) : errorText.substring(0, 500);
                            alert('Не удалось сохранить изменения.\n\nСтатус: ' + response.status + '\n' + errorMsg);
                        }
                    }
                } catch (error) {
                    console.error('Edit error:', error);
                    alert('Не удалось сохранить изменения: ' + error.message);
                }
            });
        });
        </script>
    </body>
</html>
