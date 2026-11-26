<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Новые товары — eBay Finder</title>
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

                <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-4">
                        <div>
                            <h1 class="text-2xl font-bold">Новые товары</h1>
                            <p class="text-sm text-slate-500">Объявления, найденные вашими профилями поиска</p>
                        </div>
                        <?php if($notifications->total() > 0): ?>
                            <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Отметить все прочитанными
                                </button>
                            </form>
                        <?php endif; ?>
                    </header>
                    <div class="divide-y">
                        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <article class="flex flex-wrap items-center justify-between gap-4 p-4" data-notification-list-item>
                                <div class="min-w-0">
                                    <a href="<?php echo e($notification->data['item_url']); ?>" target="_blank" rel="noopener" class="font-semibold text-blue-700 hover:underline">
                                        <?php echo e($notification->data['title']); ?>

                                    </a>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Профиль: <?php echo e($notification->data['profile_name']); ?> ·
                                        <?php echo e($notification->data['currency']); ?> <?php echo e(number_format((float) $notification->data['total_price'], 2)); ?>

                                    </p>
                                    <?php if(($notification->data['smart_alert_type'] ?? null) === 'profile_match'): ?>
                                        <p class="mt-1 text-xs font-medium text-violet-700">
                                            Известный лот найден в новом профиле. Совпадения: <?php echo e(implode(', ', $notification->data['profile_names'] ?? [])); ?>

                                        </p>
                                    <?php elseif(($notification->data['smart_alert_type'] ?? null) === 'shared_price_change'): ?>
                                        <p class="mt-1 text-xs font-medium text-emerald-700">
                                            Цена изменилась в нескольких профилях:
                                            <?php echo e($notification->data['previous_currency']); ?> <?php echo e(number_format((float) $notification->data['previous_price'], 2)); ?>

                                            → <?php echo e($notification->data['currency']); ?> <?php echo e(number_format((float) $notification->data['total_price'], 2)); ?>.
                                            Профили: <?php echo e(implode(', ', $notification->data['profile_names'] ?? [])); ?>

                                        </p>
                                    <?php endif; ?>
                                    <?php if(($duplicateProfilesByListingId[(int) ($notification->data['listing_id'] ?? 0)] ?? []) !== []): ?>
                                        <p class="mt-1 text-xs font-medium text-violet-700">
                                            Также найдено в профилях: <?php echo e(implode(', ', $duplicateProfilesByListingId[(int) $notification->data['listing_id']])); ?>

                                        </p>
                                    <?php endif; ?>
                                    <p class="mt-1 text-xs text-slate-500"><?php echo e($notification->created_at->format('d.m.Y H:i')); ?></p>
                                </div>
                                <form method="POST" action="<?php echo e(route('notifications.read', $notification)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button class="rounded border px-3 py-2 text-sm hover:bg-slate-50">Отметить прочитанным</button>
                                </form>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="p-8 text-center text-slate-500">Новых товаров пока нет. Когда автоматический поиск найдёт объявления, они появятся здесь.</p>
                        <?php endif; ?>
                    </div>
                    <div class="p-4"><?php echo e($notifications->links()); ?></div>
                </section>
            </div>
        </main>
    </body>
</html>
<?php /**PATH /home/andrei/web/ebay/resources/views/notifications/index.blade.php ENDPATH**/ ?>