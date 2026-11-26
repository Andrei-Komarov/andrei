<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Удалённые товары — Finder</title>
        <?php echo $__env->make('partials.theme-bootstrap', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <?php echo $__env->make('partials.sitebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="min-w-0">
                <?php if(session('success')): ?>
                    <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if($errors->any()): ?>
                    <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <p><?php echo e($error); ?></p>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <section class="rounded-xl bg-white shadow-sm">
                    <header class="p-4">
                        <h1 class="text-2xl font-bold">Удалённые товары</h1>
                        <p class="text-sm text-slate-500">Удалённые товары можно восстановить в список.</p>
                    </header>

                    <form id="deleted-listings-bulk-delete" method="POST" action="<?php echo e(route('listings.bulk-force-destroy')); ?>"
                        class="flex flex-wrap items-center gap-3 px-4 pb-4"
                        data-confirm-dialog-form
                        data-confirm-message="bulk-delete"
                        data-confirm-button="Удалить навсегда">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="rounded border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                            Удалить выбранные навсегда
                        </button>
                        <span class="text-sm text-slate-500" data-deleted-selection-count></span>
                        <div class="ml-auto">
                            <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'deleted-listings', 'containerClass' => 'p-0'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="w-8 p-2" data-column-key="selection">
                                        <input type="checkbox" class="h-4 w-4" data-deleted-select-all aria-label="Выбрать все товары">
                                    </th>
                                    <th class="p-3" data-column-key="image">Фото</th>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'listing', 'sortKey' => 'title', 'label' => 'Товар', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'price', 'sortKey' => 'price', 'label' => 'Цена', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'deleted', 'sortKey' => 'deleted', 'label' => 'Удалён', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th class="p-3" data-column-key="actions">Действие</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__empty_1 = true; $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php ($listingImages = collect($listing->galleryImageUrls())); ?>
                                    <tr>
                                        <td class="w-8 p-2">
                                            <input type="checkbox" class="h-4 w-4" name="listing_ids[]" value="<?php echo e($listing->id); ?>"
                                                form="deleted-listings-bulk-delete" data-deleted-listing-select
                                                aria-label="Выбрать <?php echo e($listing->title); ?>">
                                        </td>
                                        <td class="p-3">
                                            <?php if($listingImages->isNotEmpty()): ?>
                                                <button
                                                    type="button"
                                                    class="block rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    aria-label="Открыть фотографии товара: <?php echo e($listing->title); ?>"
                                                    data-listing-image-gallery
                                                    data-image-gallery="<?php echo e(json_encode($listingImages, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>"
                                                    data-image-title="<?php echo e($listing->title); ?>"
                                                >
                                                    <img src="<?php echo e($listing->image_url ?: $listingImages->first()); ?>" alt="Фото товара: <?php echo e($listing->title); ?>" loading="lazy" class="h-16 w-20 rounded border border-slate-200 object-contain">
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">Нет фото</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <a class="font-medium text-blue-700 hover:underline" href="<?php echo e($listing->item_url); ?>" target="_blank" rel="noopener"><?php echo e($listing->title); ?></a>
                                            <div class="text-xs text-slate-500"><?php echo e($listing->searchProfiles->pluck('name')->join(', ')); ?></div>
                                        </td>
                                        <td class="p-3 font-semibold">$<?php echo e(number_format((float) $listing->total_price, 2)); ?></td>
                                        <td class="p-3 text-xs text-slate-600"><?php echo e($listing->deleted_at->format('d.m.Y H:i')); ?></td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="<?php echo e(route('listings.restore', $listing->id)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Восстановить</button>
                                            </form>
                                            <form method="POST" action="<?php echo e(route('listings.force-destroy', $listing->id)); ?>"
                                                data-confirm-dialog-form
                                                data-confirm-button="Удалить навсегда"
                                                data-confirm-message="Удалить товар окончательно? Это действие нельзя отменить.">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Удалить навсегда</button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" data-column-empty-state class="p-8 text-center text-slate-500">Удалённых товаров пока нет.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <?php if($showPerPageSelector): ?>
                                <form method="GET" action="<?php echo e(route('listings.deleted', request()->except(['page', 'per_page']))); ?>" class="flex items-center gap-2">
                                    <label for="deleted-listings-per-page" class="text-sm text-slate-600">Показывать на странице</label>
                                    <select
                                        id="deleted-listings-per-page"
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
                            <p class="text-sm text-slate-700" data-deleted-listings-pagination-count>
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
                            <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>" data-deleted-listings-pagination>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/listings/deleted.blade.php ENDPATH**/ ?>