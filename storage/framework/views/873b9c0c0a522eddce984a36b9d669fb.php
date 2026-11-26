<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title><?php echo e($listingSectionTitle); ?> — Finder</title>
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
            <?php if($errors->any()): ?>
                <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                    <p class="font-medium">Проверьте заполненные поля.</p>
                    <ul class="mt-1 list-inside list-disc">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold"><?php echo e($listingSectionTitle); ?></h1>
                    <p class="text-sm text-slate-500"><?php echo e($listingSectionDescription); ?></p>
                </div>

                <form id="listing-filters-form" method="GET" action="<?php echo e(route('listings.index')); ?>" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label for="profile_id" class="text-sm font-medium">
                        Фильтр по профилю
                        <select id="profile_id" name="profile_id" class="mt-1 w-full rounded border p-2 font-normal">
                            <option value="">Все профили</option>
                            <?php $__currentLoopData = $profiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($profile->id); ?>" <?php if((string) request('profile_id') === (string) $profile->id): echo 'selected'; endif; ?>>
                                    <?php echo e($profile->name); ?> — товаров: <?php echo e($profile->listings_count); ?>

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
                            <option value="price_asc" <?php if($sort === 'price_asc'): echo 'selected'; endif; ?>>Цена: сначала дешевле</option>
                            <option value="price_desc" <?php if($sort === 'price_desc'): echo 'selected'; endif; ?>>Цена: сначала дороже</option>
                            <option value="condition_asc" <?php if($sort === 'condition_asc'): echo 'selected'; endif; ?>>Состояние: А—Я</option>
                            <option value="condition_desc" <?php if($sort === 'condition_desc'): echo 'selected'; endif; ?>>Состояние: Я—А</option>
                            <option value="date_desc" <?php if($sort === 'date_desc'): echo 'selected'; endif; ?>>Дата: сначала новые</option>
                            <option value="date_asc" <?php if($sort === 'date_asc'): echo 'selected'; endif; ?>>Дата: сначала старые</option>
                        </select>
                    </label>

                </form>

                <form id="listing-search-form" method="GET" action="<?php echo e(route('listings.index')); ?>" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                            <option value="title" <?php if(($filters['search_in'] ?? 'title') === 'title'): echo 'selected'; endif; ?>>Название лота</option>
                            <option value="seller_description" <?php if(($filters['search_in'] ?? '') === 'seller_description'): echo 'selected'; endif; ?>>Описание продавца</option>
                        </select>
                    </label>

                </form>

                <div class="mt-4 flex justify-end gap-3">
                    <a href="<?php echo e(route('listings.index')); ?>" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Сбросить фильтры</a>
                    <button type="submit" form="listing-filters-form" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Применить</button>
                </div>

                <?php if($selectedProfile): ?>
                    <p class="mt-3 text-sm text-slate-700">
                        В профиле «<?php echo e($selectedProfile->name); ?>» всего товаров: <strong><?php echo e($selectedProfile->listings_count); ?></strong>.
                        <?php if($listings->total() !== $selectedProfile->listings_count): ?>
                            Сейчас в таблице показано <?php echo e($listings->total()); ?> товаров с учётом остальных фильтров.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </section>

            <section class="rounded-xl bg-white shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 p-4">
                    <form id="bulk-listings-form" method="POST" action="<?php echo e(route('listings.bulk-action')); ?>" class="flex flex-wrap items-end gap-3" data-bulk-listings>
                        <?php echo csrf_field(); ?>
                        <label for="bulk_action" class="sr-only">Выберите действие для товаров</label>
                        <select id="bulk_action" name="bulk_action" aria-label="Выберите действие для товаров" required class="block rounded border p-2 font-normal">
                            <option value="">Выберите действие</option>
                            <?php if(($filters['status'] ?? '') === 'buy'): ?>
                                <option value="unbuy">Вернуть в список</option>
                                <option value="delete">Переместить в удалённые</option>
                            <?php else: ?>
                                <option value="new">Статус: Новый</option>
                                <option value="watching">Статус: Наблюдаю</option>
                                <option value="buy">Статус: Купить</option>
                                <option value="skipped">Статус: Пропустить</option>
                                <option value="delete">Переместить в удалённые</option>
                            <?php endif; ?>
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
                        <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'listings', 'containerClass' => 'p-0'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="w-8 px-2 py-3" data-column-key="select" data-column-label="Выбор"><input type="checkbox" aria-label="Выбрать все товары на странице" data-select-all-listings></th>
                                <th class="p-3" data-column-key="image">Фото</th>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'listing', 'sortKey' => 'title', 'label' => 'Лот', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'brand', 'sortKey' => 'brand', 'label' => 'Бренд', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'statuses', 'sortKey' => 'status', 'label' => 'Статусы', 'sort' => $sort, 'class' => 'w-36 min-w-36 p-3'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'value', 'sortKey' => 'value', 'label' => 'Оценка выгодности', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'condition', 'sortKey' => 'condition', 'label' => 'Состояние', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'added', 'sortKey' => 'date', 'label' => 'Дата появления', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'specifications', 'sortKey' => 'specifications', 'label' => 'Характеристики', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'seller', 'sortKey' => 'seller', 'label' => 'Продавец', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'seller-description', 'sortKey' => 'seller_description', 'label' => 'Описание продавца', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'note', 'sortKey' => 'note', 'label' => 'Заметка к товару', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <th class="w-16 px-2 py-3 text-center" data-column-key="checked" data-column-label="Проверено" title="Отметить товар как проверенный">✓</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" data-listings-tbody data-current-status="<?php echo e($filters['status'] ?? ''); ?>">
                            <?php $__empty_1 = true; $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php ($listingMatches = $listingGroupsById->get($listing->id, collect([$listing]))); ?>
                                <?php ($listingImages = collect($listing->galleryImageUrls())); ?>
                                <tr data-listing-row data-listing-id="<?php echo e($listing->id); ?>" data-listing-status="<?php echo e($listing->status); ?>">
                                    <td class="w-8 px-2 py-3">
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
                                    <td class="p-3">
                                        <a class="font-medium text-blue-700 hover:underline" href="<?php echo e($listing->item_url); ?>" target="_blank" rel="noopener"><?php echo e($listing->title); ?></a>
                                        <details class="mt-1 text-xs text-slate-600">
                                            <summary class="cursor-pointer font-medium text-violet-700 hover:text-violet-900">
                                                Найден в <?php echo e($listing->searchProfiles->count()); ?> <?php echo e($listing->searchProfiles->count() === 1 ? 'профиле' : 'профилях'); ?>

                                            </summary>
                                            <ul class="mt-2 space-y-2">
                                                <?php $__currentLoopData = $listing->searchProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li class="rounded border border-slate-200 p-2">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <a href="<?php echo e(route('profiles.edit', $profile)); ?>" class="font-medium text-blue-700 hover:underline"><?php echo e($profile->name); ?></a>
                                                        </div>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        </details>
                                    </td>
                                    <?php ($listingBrand = $displaySpecifications->get($listing->id)['brand'] ?? null); ?>
                                    <td class="p-3" data-listing-brand>
                                        <?php echo e($listingBrand ?: '—'); ?>

                                    </td>
                                    <td class="w-36 min-w-36 p-3">
                                        <?php ($listingStatusScope = $listing->searchProfiles->count() > 1 ? 'Все профили' : $listing->searchProfiles->first()->name); ?>
                                        <?php ($listingCurrentStatus = $listing->status); ?>
                                        <div data-profile-listing-id="<?php echo e($listing->id); ?>" data-status="<?php echo e($listingCurrentStatus); ?>" class="min-w-0">
                                            <?php if($listing->searchProfiles->count() > 1): ?>
                                                <span class="mb-1 block text-xs font-medium text-slate-600"><?php echo e($listingStatusScope); ?></span>
                                            <?php endif; ?>
                                            <form method="POST" action="<?php echo e(route('listings.update', array_merge(request()->query(), ['listing' => $listing]))); ?>" class="flex shrink-0 flex-nowrap gap-0.5" data-listing-status-form>
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <?php $__currentLoopData = [
                                                                'new' => ['Новый', 'M12 3l1.8 6.2L20 11l-6.2 1.8L12 19l-1.8-6.2L4 11l6.2-1.8L12 3Zm7 12 .7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15Z'],
                                                                'watching' => ['Наблюдаю', 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
                                                                'buy' => ['Купить', 'M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2m-3 5v4m-2-2h4'],
                                                                'skipped' => ['Пропустить', 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM7 12h10'],
                                                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusValue => [$statusLabel, $iconPath]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <button
                                                        type="submit"
                                                        name="status"
                                                        value="<?php echo e($statusValue); ?>"
                                                        title="<?php echo e($listingStatusScope); ?>: установить статус «<?php echo e($statusLabel); ?>»"
                                                        aria-label="<?php echo e($listingStatusScope); ?>: установить статус «<?php echo e($statusLabel); ?>»"
                                                        <?php if($listingCurrentStatus === $statusValue): echo 'disabled'; endif; ?>
                                                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border <?php echo e($listingCurrentStatus === $statusValue ? 'border-blue-200 bg-blue-50 font-semibold text-blue-800' : 'border-slate-300 text-slate-600 hover:bg-slate-100'); ?>"
                                                    >
                                                        <svg class="h-3.5 w-3.5" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="<?php echo e($iconPath); ?>" />
                                                        </svg>
                                                        <span class="sr-only"><?php echo e($statusLabel); ?></span>
                                                    </button>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="p-3 font-semibold">
                                        $<?php echo e(number_format((float) $listing->total_price, 2)); ?>

                                        <div class="text-xs font-normal text-slate-500">товар $<?php echo e(number_format((float) $listing->price, 2)); ?> + доставка <?php echo e($listing->shipping_type === 'CALCULATED' ? 'CALCULATED' : '$' . number_format((float) $listing->shipping_price, 2)); ?></div>
                                        <a href="<?php echo e(route('listings.price-history', $listing)); ?>" class="mt-1 block text-xs font-normal text-blue-700 hover:underline">История цены</a>
                                    </td>
                                    <?php ($assessment = $valueAssessments[$listing->id]); ?>
                                    <td class="max-w-64 p-3">
                                        <?php if($assessment['score'] === null): ?>
                                            <span class="font-medium text-slate-600">Недостаточно данных</span>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Найдено похожих товаров: <?php echo e($assessment['comparison_count']); ?> из 3 необходимых.
                                            </p>
                                        <?php else: ?>
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800' => $assessment['score'] >= 75,
                                                'bg-amber-100 text-amber-800' => $assessment['score'] >= 50 && $assessment['score'] < 75,
                                                'bg-red-100 text-red-800' => $assessment['score'] < 50,
                                            ]); ?>">
                                                <?php echo e($assessment['score']); ?> / 100
                                            </span>
                                            <details class="mt-1 text-xs text-slate-600">
                                                <summary class="cursor-pointer hover:text-blue-700">
                                                    Факторы · аналогов: <?php echo e($assessment['comparison_count']); ?>

                                                </summary>
                                                <ul class="mt-2 space-y-2">
                                                    <?php $__currentLoopData = $assessment['factors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $factor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <li>
                                                            <span class="font-medium"><?php echo e($factor['label']); ?>: <?php echo e($factor['score']); ?>/<?php echo e($factor['maximum']); ?></span>
                                                            <span class="block"><?php echo e($factor['explanation']); ?></span>
                                                        </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ul>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3"><?php echo e($listing->condition ?? '—'); ?></td>
                                    <td class="p-3 text-xs text-slate-600"><?php echo e($listing->created_at->format('d.m.Y H:i')); ?></td>
                                    <?php ($specifications = $displaySpecifications[$listing->id]); ?>
                                    <td class="p-3 text-xs" data-listing-specifications>
                                        <?php if(filled($listing->specifications)): ?>
                                            <dl class="grid min-w-48 grid-cols-[auto_1fr] gap-x-2 gap-y-1">
                                                <?php $__currentLoopData = $listing->specifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $aspectName => $aspectValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <dt class="font-medium"><?php echo e($aspectName); ?>:</dt>
                                                    <dd class="break-words"><?php echo e(is_array($aspectValue) ? implode(', ', array_filter($aspectValue, 'is_scalar')) : ($aspectValue ?? '—')); ?></dd>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </dl>
                                        <?php else: ?>
                                            CPU: <?php echo e($specifications['cpu'] ?? '—'); ?><br>
                                            RAM: <?php echo e($specifications['ram_gb'] ? $specifications['ram_gb'].' GB' : '—'); ?> · SSD: <?php echo e($specifications['storage'] ?? '—'); ?><br>
                                            Экран: <?php echo e($specifications['screen'] ?? '—'); ?> · GPU: <?php echo e($specifications['gpu'] ?? '—'); ?>

                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <?php echo e($listing->seller ?? '—'); ?>

                                        <div class="text-xs text-slate-500"><?php echo e($listing->seller_feedback ? $listing->seller_feedback.'%' : ''); ?></div>
                                    </td>
                                    <td class="max-w-sm p-3 text-xs" data-listing-seller-description>
                                        <div class="max-h-40 overflow-y-auto whitespace-pre-line break-words"><?php echo e($listing->seller_description ?: '—'); ?></div>
                                    </td>
                                    <td class="p-3">
                                        <form
                                            method="POST"
                                            action="<?php echo e(route('listings.notes.update', $listing)); ?>"
                                            class="space-y-2"
                                            data-listing-note-form
                                        >
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <label for="listing-note-<?php echo e($listing->id); ?>" class="sr-only">Заметка к товару: <?php echo e($listing->title); ?></label>
                                            <textarea
                                                id="listing-note-<?php echo e($listing->id); ?>"
                                                name="note"
                                                rows="3"
                                                maxlength="2000"
                                                placeholder="Например, проверить продавца"
                                                class="w-full rounded border border-slate-300 p-2 text-sm font-normal"
                                                data-listing-note
                                            ><?php echo e(old('note', $listingNotesByEbayItemId[$listing->ebay_item_id] ?? '')); ?></textarea>
                                            <span class="block text-xs text-slate-500" role="status" aria-live="polite" data-note-save-status></span>
                                        </form>
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
                                    <td colspan="14" data-column-empty-state class="p-8 text-center text-slate-500">Лотов пока нет. Создайте профиль и запустите <code>php artisan ebay:import-laptops</code>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <?php if($showPerPageSelector): ?>
                            <form method="GET" action="<?php echo e(route('listings.index', request()->except(['page', 'per_page']))); ?>" class="flex items-center gap-2">
                                <label for="listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                <select
                                    id="listings-per-page"
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
                        <p class="text-sm text-slate-700" data-listings-pagination-count>
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
                        <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>" data-listings-pagination>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/listings/index.blade.php ENDPATH**/ ?>