<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title>Исключённый товар — Finder</title>
        <?php echo $__env->make('partials.theme-bootstrap', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <?php echo $__env->make('partials.sitebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="min-w-0">
            <div data-listing-action-status class="mb-4 hidden rounded p-3" role="status" aria-live="polite"></div>
            <?php if(session('success')): ?>
                <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

                <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <h1 class="text-2xl font-bold">Исключённый товар</h1>
                        <p class="text-sm text-slate-500">Товары, отфильтрованные по словам исключения. Система нашла совпадение в названии лота или описании продавца.</p>
                    </div>

                    <form id="listing-filters-form" method="GET" action="<?php echo e(route('listings.excluded')); ?>" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label for="profile_id" class="text-sm font-medium">
                            Фильтр по профилю
                            <select id="profile_id" name="profile_id" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Все профили</option>
                                <?php $__currentLoopData = $profiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($profile->id); ?>" <?php if((string) request('profile_id') === (string) $profile->id): echo 'selected'; endif; ?>>
                                        <?php echo e($profile->name); ?> — товаров: <?php echo e($profile->profile_listings_count); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </label>

                        <label for="status" class="text-sm font-medium">
                            Статус
                            <select id="status" name="status" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Любой статус</option>
                                <option value="new" <?php if(($filters['status'] ?? '') === 'new'): echo 'selected'; endif; ?>>Новый</option>
                                <option value="watching" <?php if(($filters['status'] ?? '') === 'watching'): echo 'selected'; endif; ?>>Наблюдаю</option>
                                <option value="buy" <?php if(($filters['status'] ?? '') === 'buy'): echo 'selected'; endif; ?>>Купить</option>
                                <option value="skipped" <?php if(($filters['status'] ?? '') === 'skipped'): echo 'selected'; endif; ?>>Пропустить</option>
                            </select>
                        </label>

                        <label for="price_min" class="text-sm font-medium">
                            Цена от, USD
                            <input id="price_min" name="price_min" type="number" min="0" step="0.01" value="<?php echo e($filters['price_min'] ?? ''); ?>" class="mt-1 w-full rounded border p-2 font-normal">
                        </label>

                        <label for="price_max" class="text-sm font-medium">
                            Цена до, USD
                            <input id="price_max" name="price_max" type="number" min="0" step="0.01" value="<?php echo e($filters['price_max'] ?? ''); ?>" class="mt-1 w-full rounded border p-2 font-normal">
                        </label>

                        <label for="condition" class="text-sm font-medium">
                            Состояние
                            <select id="condition" name="condition" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Любое состояние</option>
                                <?php $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($condition); ?>" <?php if(($filters['condition'] ?? '') === $condition): echo 'selected'; endif; ?>><?php echo e($condition); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </label>

                        <label for="brand" class="text-sm font-medium">
                            Бренд
                            <select id="brand" name="brand" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Любой бренд</option>
                                <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($brand); ?>" <?php if(($filters['brand'] ?? '') === $brand): echo 'selected'; endif; ?>><?php echo e($brand); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </label>

                        <label for="processor" class="text-sm font-medium">
                            Процессор
                            <select id="processor" name="processor" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Любой процессор</option>
                                <?php $__currentLoopData = $processors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $processor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($processor); ?>" <?php if(($filters['processor'] ?? '') === $processor): echo 'selected'; endif; ?>><?php echo e($processor); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </label>

                        <label for="ram" class="text-sm font-medium">
                            Объём RAM
                            <select id="ram" name="ram" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="">Любой объём</option>
                                <?php $__currentLoopData = $rams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ram): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($ram); ?>" <?php if((int) ($filters['ram'] ?? 0) === $ram): echo 'selected'; endif; ?>><?php echo e($ram); ?> GB</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </label>

                        <label for="date_from" class="text-sm font-medium">
                            Добавлено с
                            <input id="date_from" name="date_from" type="date" value="<?php echo e($filters['date_from'] ?? ''); ?>" class="mt-1 w-full rounded border p-2 font-normal">
                        </label>

                        <label for="date_to" class="text-sm font-medium">
                            Добавлено по
                            <input id="date_to" name="date_to" type="date" value="<?php echo e($filters['date_to'] ?? ''); ?>" class="mt-1 w-full rounded border p-2 font-normal">
                        </label>

                        <label for="sort" class="text-sm font-medium">
                            Сортировка
                            <select id="sort" name="sort" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="date_desc" <?php if($sort === 'date_desc'): echo 'selected'; endif; ?>>Дата: сначала новые</option>
                                <option value="date_asc" <?php if($sort === 'date_asc'): echo 'selected'; endif; ?>>Дата: сначала старые</option>
                                <option value="price_asc" <?php if($sort === 'price_asc'): echo 'selected'; endif; ?>>Цена: сначала дешевле</option>
                                <option value="price_desc" <?php if($sort === 'price_desc'): echo 'selected'; endif; ?>>Цена: сначала дороже</option>
                                <option value="title_asc" <?php if($sort === 'title_asc'): echo 'selected'; endif; ?>>Название: А—Я</option>
                                <option value="title_desc" <?php if($sort === 'title_desc'): echo 'selected'; endif; ?>>Название: Я—А</option>
                                <option value="condition_asc" <?php if($sort === 'condition_asc'): echo 'selected'; endif; ?>>Состояние: А—Я</option>
                                <option value="condition_desc" <?php if($sort === 'condition_desc'): echo 'selected'; endif; ?>>Состояние: Я—А</option>
                                <option value="seller_asc" <?php if($sort === 'seller_asc'): echo 'selected'; endif; ?>>Продавец: А—Я</option>
                                <option value="seller_desc" <?php if($sort === 'seller_desc'): echo 'selected'; endif; ?>>Продавец: Я—А</option>
                            </select>
                        </label>

                    </form>

                    <form id="listing-search-form" method="GET" action="<?php echo e(route('listings.excluded')); ?>" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <input type="hidden" name="profile_id" value="<?php echo e($filters['profile_id'] ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo e($filters['status'] ?? ''); ?>">
                        <input type="hidden" name="price_min" value="<?php echo e($filters['price_min'] ?? ''); ?>">
                        <input type="hidden" name="price_max" value="<?php echo e($filters['price_max'] ?? ''); ?>">
                        <input type="hidden" name="condition" value="<?php echo e($filters['condition'] ?? ''); ?>">
                        <input type="hidden" name="brand" value="<?php echo e($filters['brand'] ?? ''); ?>">
                        <input type="hidden" name="processor" value="<?php echo e($filters['processor'] ?? ''); ?>">
                        <input type="hidden" name="ram" value="<?php echo e($filters['ram'] ?? ''); ?>">
                        <input type="hidden" name="date_from" value="<?php echo e($filters['date_from'] ?? ''); ?>">
                        <input type="hidden" name="date_to" value="<?php echo e($filters['date_to'] ?? ''); ?>">
                        <input type="hidden" name="sort" value="<?php echo e($sort); ?>">

                        <label for="search" class="text-sm font-medium">
                            Поиск
                            <input id="search" name="search" type="search" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="Введите текст для поиска" class="mt-1 w-full rounded border p-2 font-normal">
                        </label>

                        <label for="search_in" class="text-sm font-medium">
                            Искать в
                            <select id="search_in" name="search_in" class="mt-1 w-full rounded border p-2 font-normal">
                                <option value="title" <?php if(($filters['search_in'] ?? 'title') === 'title'): echo 'selected'; endif; ?>>Название товара</option>
                                <option value="seller_description" <?php if(($filters['search_in'] ?? 'title') === 'seller_description'): echo 'selected'; endif; ?>>Описание продавца</option>
                            </select>
                        </label>

                    </form>

                    <div class="mt-4 flex justify-end gap-3">
                        <a href="<?php echo e(route('listings.excluded')); ?>" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Сбросить фильтры</a>
                        <button type="submit" form="listing-filters-form" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Применить</button>
                    </div>
                </section>

                <section class="rounded-xl bg-white shadow-sm">
                    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 p-4">
                        <form id="bulk-listings-form" method="POST" action="<?php echo e(route('listings.bulk-action')); ?>" class="flex flex-wrap items-end gap-3" data-bulk-listings>
                            <?php echo csrf_field(); ?>
                            <label for="bulk_action" class="sr-only">Выберите действие для товаров</label>
                            <select id="bulk_action" name="bulk_action" aria-label="Выберите действие для товаров" required class="block rounded border p-2 font-normal">
                                <option value="">Выберите действие</option>
                                <option value="unexclude">Список товаров</option>
                                <option value="new">Статус: Новый</option>
                                <option value="watching">Статус: Наблюдаю</option>
                                <option value="buy">Статус: Купить</option>
                                <option value="skipped">Статус: Пропустить</option>
                                <option value="delete">Переместить в удалённые</option>
                            </select>
                            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50" data-bulk-submit disabled>Применить</button>
                            <span class="text-sm text-slate-500" data-bulk-selection-count role="status" aria-live="polite"></span>
                        </form>
                        <div class="ml-auto flex items-center gap-2">
                            <form method="POST" action="<?php echo e(route('listings.export')); ?>" data-export-listings>
                                <?php echo csrf_field(); ?>
                                <button type="submit" aria-label="Скачать выбранные CSV (Excel)" title="Скачать выбранные CSV (Excel)" class="rounded border border-emerald-700 p-2 text-emerald-800 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50" data-export-submit disabled>
                                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4" />
                                    </svg>
                                </button>
                            </form>
                            <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'excluded', 'containerClass' => 'p-0'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="w-8 px-2 py-3" data-column-key="select" data-column-label="Выбор"><input type="checkbox" aria-label="Выбрать все товары на странице" data-select-all-listings></th>
                                    <th class="p-3" data-column-key="image">Фото</th>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'title', 'sortKey' => 'title', 'label' => 'Лот', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th class="p-3" data-column-key="reason">Причина</th>
                                    <th class="p-3" data-column-key="profile">Профиль</th>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'condition', 'sortKey' => 'condition', 'label' => 'Состояние', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'date', 'label' => 'Дата', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th class="p-3" data-column-key="seller">Продавец</th>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'seller-description', 'sortKey' => 'seller_description', 'label' => 'Описание продавца', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th class="w-16 px-2 py-3 text-center" data-column-key="checked" data-column-label="Проверено" title="Отметить товар как проверенный">✓</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__empty_1 = true; $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php ($listingImages = collect($listing->galleryImageUrls())); ?>
                                    <tr data-listing-row data-listing-id="<?php echo e($listing->id); ?>">
                                        <td class="w-8 px-2 py-3">
                                            <?php ($listingMatches = $listingGroupsById->get($listing->id, collect([$listing]))); ?>
                                            <input
                                                type="checkbox"
                                                name="listing_ids[]"
                                                value="<?php echo e($listing->id); ?>"
                                                data-listing-ids='<?php echo json_encode($listingMatches->pluck("id")->all(), 15, 512) ?>'
                                                form="bulk-listings-form"
                                                aria-label="Выбрать товар: <?php echo e($listing->title); ?>"
                                                data-listing-select
                                            >
                                        </td>
                                        <td class="p-3">
                                            <?php if($listingImages->isNotEmpty()): ?>
                                                <button
                                                    type="button"
                                                    class="block h-16 w-20 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    aria-label="Открыть фотографии товара: <?php echo e($listing->title); ?>"
                                                    data-listing-image-gallery
                                                    data-image-gallery="<?php echo e(json_encode($listingImages, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>"
                                                    data-image-title="<?php echo e($listing->title); ?>"
                                                >
                                                    <img src="<?php echo e($listing->image_url ?: $listingImages->first()); ?>" alt="Фото товара: <?php echo e($listing->title); ?>" loading="lazy" width="80" height="64" class="h-full w-full rounded border border-slate-200 bg-slate-200 object-contain" style="aspect-ratio:80/64;">
                                                </button>
                                            <?php else: ?>
                                                <div class="flex h-16 w-20 items-center justify-center">
                                                    <span class="text-xs text-slate-400">Нет фото</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 max-w-xs">
                                            <a href="<?php echo e($listing->item_url); ?>" target="_blank" rel="noopener noreferrer" class="line-clamp-2 block text-sm font-medium text-blue-700 hover:underline"><?php echo e($listing->title); ?></a>
                                        </td>
                                        <td class="p-3">
                                            <span class="font-medium"><?php echo e(number_format((float) $listing->total_price, 2)); ?> <?php echo e($listing->currency); ?></span>
                                        </td>
                                        <td class="p-3">
                                            <?php if($listing->excluded_reason): ?>
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                                    <?php echo e(Str::limit($listing->excluded_reason, 50)); ?>

                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-xs">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <?php $__currentLoopData = $listing->searchProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <p class="text-sm"><?php echo e($profile->name ?? '—'); ?></p>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                <?php echo e($listing->condition ?? '—'); ?>

                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <p class="text-xs text-slate-500"><?php echo e($listing->created_at->format('d.m.Y H:i')); ?></p>
                                        </td>
                                        <td class="p-3">
                                            <p class="text-sm"><?php echo e($listing->seller ?? '—'); ?></p>
                                        </td>
                                        <td class="max-w-sm p-3 text-xs" data-listing-seller-description>
                                            <div class="max-h-40 overflow-y-auto whitespace-pre-line break-words"><?php echo e($listing->seller_description ?: '—'); ?></div>
                                        </td>
                                        <td class="w-16 px-2 py-3 text-center">
                                            <label class="inline-flex cursor-pointer items-center justify-center" title="<?php echo e($listing->checked ? 'Снять отметку' : 'Отметить как проверенный'); ?>">
                                                <input
                                                    type="checkbox"
                                                    class="sr-only peer"
                                                    data-listing-checked
                                                    data-listing-id="<?php echo e($listing->id); ?>"
                                                    <?php echo e($listing->checked ? 'checked' : ''); ?>

                                                    aria-label="<?php echo e($listing->checked ? 'Товар проверен' : 'Товар не проверен'); ?>"
                                                >
                                                <span class="flex h-6 w-6 items-center justify-center rounded border border-slate-300 text-sm text-slate-400 transition-colors hover:border-blue-400 hover:text-blue-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-600">
                                                    <?php echo e($listing->checked ? '✓' : ''); ?>

                                                </span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="11" data-column-empty-state class="p-8 text-center text-slate-500">Исключённых товаров пока нет.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <?php if($showPerPageSelector): ?>
                                <form method="GET" action="<?php echo e(route('listings.excluded', request()->except(['page', 'per_page']))); ?>" class="flex items-center gap-2">
                                    <label for="excluded-listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                    <select
                                        id="excluded-listings-per-page"
                                        name="per_page"
                                        class="rounded border border-slate-300 bg-white px-2 py-1 text-sm"
                                        onchange="this.form.submit()"
                                    >
                                        <?php $__currentLoopData = [25, 50, 100, 200]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pageSize): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($pageSize); ?>" <?php if($perPage === $pageSize): echo 'selected'; endif; ?>><?php echo e($pageSize); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                            <p class="text-sm text-slate-700">
                                <?php echo __('Showing'); ?>

                                <?php if($listings->firstItem()): ?>
                                    <span class="font-medium"><?php echo e($listings->firstItem()); ?></span>
                                    <?php echo __('to'); ?>

                                    <span class="font-medium"><?php echo e($listings->lastItem()); ?></span>
                                <?php else: ?>
                                    <?php echo e($listings->count()); ?>

                                <?php endif; ?>
                                <?php echo __('of'); ?>

                                <span class="font-medium"><?php echo e($listings->total()); ?></span>
                                <?php echo __('results'); ?>

                            </p>
                        </div>
                        <?php if($listings->hasPages()): ?>
                            <?php ($visibleLastPage = min($listings->lastPage(), max(5, $listings->currentPage() + 4))); ?>
                            <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>">
                                <div class="flex items-center">
                                    <?php if($listings->onFirstPage()): ?>
                                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.previous')); ?>" class="rounded-l border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-400">‹</span>
                                    <?php else: ?>
                                        <a href="<?php echo e($listings->previousPageUrl()); ?>" rel="prev" aria-label="<?php echo e(__('pagination.previous')); ?>" class="rounded-l border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">‹</a>
                                    <?php endif; ?>
                                    <?php $__currentLoopData = $listings->getUrlRange(1, $visibleLastPage); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($page === $listings->currentPage()): ?>
                                            <span aria-current="page" data-page-number="<?php echo e($page); ?>" class="border-y border-slate-300 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700"><?php echo e($page); ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo e($url); ?>" aria-label="<?php echo e(__('Go to page :page', ['page' => $page])); ?>" data-page-number="<?php echo e($page); ?>" class="border-y border-r border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><?php echo e($page); ?></a>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($listings->hasMorePages()): ?>
                                        <a href="<?php echo e($listings->nextPageUrl()); ?>" rel="next" aria-label="<?php echo e(__('pagination.next')); ?>" class="rounded-r border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">›</a>
                                    <?php else: ?>
                                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.next')); ?>" class="rounded-r border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-400">›</span>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
        <?php echo $__env->make('partials.listing-image-gallery', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('partials.confirm-dialog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </body>
</html>
<?php /**PATH /home/andrei/web/ebay/resources/views/listings/excluded.blade.php ENDPATH**/ ?>