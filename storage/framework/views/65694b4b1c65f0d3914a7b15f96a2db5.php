<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Сводка по поиску — eBay Finder</title>
        <?php echo $__env->make('partials.theme-bootstrap', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <?php echo $__env->make('partials.sitebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="min-w-0">
                <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Итоги поиска">
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Найдено в последних запусках</p>
                        <p class="mt-2 text-3xl font-bold"><?php echo e(number_format($foundCount)); ?></p>
                    </article>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Новых товаров в последних запусках</p>
                        <p class="mt-2 text-3xl font-bold"><?php echo e(number_format($newListingCount)); ?></p>
                    </article>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Всего товаров в базе</p>
                        <p class="mt-2 text-3xl font-bold"><?php echo e(number_format($listingCount)); ?></p>
                    </article>
                    <a href="<?php echo e(route('listings.watchlist')); ?>" class="rounded-xl bg-white p-5 shadow-sm hover:ring-2 hover:ring-blue-200">
                        <p class="text-sm text-slate-500">В списке «Наблюдение»</p>
                        <p class="mt-2 text-3xl font-bold"><?php echo e(number_format($watchingCount)); ?></p>
                    </a>
                    <a href="<?php echo e(route('listings.buy')); ?>" class="rounded-xl bg-white p-5 shadow-sm hover:ring-2 hover:ring-blue-200">
                        <p class="text-sm text-slate-500">В списке покупок</p>
                        <p class="mt-2 text-3xl font-bold"><?php echo e(number_format($buyCount)); ?></p>
                    </a>
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Профилей давно не запускались</p>
                        <p class="mt-2 text-3xl font-bold <?php echo e($overdueProfiles->isNotEmpty() ? 'text-amber-700' : 'text-emerald-700'); ?>"><?php echo e($overdueProfiles->count()); ?></p>
                    </article>
                </section>

                <?php if($overdueProfiles->isNotEmpty()): ?>
                    <section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4" aria-labelledby="overdue-profiles-heading">
                        <h2 id="overdue-profiles-heading" class="font-semibold text-amber-950">Профили требуют запуска</h2>
                        <ul class="mt-2 list-inside list-disc text-sm text-amber-900">
                            <?php $__currentLoopData = $overdueProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <a href="<?php echo e(route('profiles.edit', $profile)); ?>" class="font-medium underline"><?php echo e($profile->name); ?></a>
                                    —
                                    <?php echo e($profile->last_synced_at
                                        ? 'последний запуск '.$profile->last_synced_at->timezone('Asia/Bishkek')->format('d.m.Y H:i')
                                        : 'ещё не запускался'); ?>

                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="rounded-xl bg-white shadow-sm">
                    <div class="border-b p-4">
                        <h2 class="font-semibold">Результаты по профилям</h2>
                        <p class="mt-1 text-xs text-slate-500">Профиль считается просроченным, если не запускался дольше своего интервала расписания. Отключённые профили не считаются просроченными.</p>
                    </div>
                    <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'search-summary'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'profile', 'sortKey' => 'profile', 'label' => 'Профиль', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'found', 'sortKey' => 'found', 'label' => 'Последний поиск: найдено', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'new-listings', 'sortKey' => 'new_listings', 'label' => 'Новых товаров', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'total', 'sortKey' => 'total', 'label' => 'В базе', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'watching', 'sortKey' => 'watching', 'label' => 'Наблюдение', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'buy', 'sortKey' => 'buy', 'label' => 'Список покупок', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'last-run', 'sortKey' => 'last_run', 'label' => 'Последний запуск', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th scope="col" class="p-3 text-slate-500" data-column-key="schedule" data-column-label="Расписание">Расписание</th>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'status', 'sortKey' => 'status', 'label' => 'Состояние', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__empty_1 = true; $__currentLoopData = $profiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php ($activity = $latestActivities->get($profile->id)); ?>
                                    <?php ($isOverdue = $overdueProfiles->contains('id', $profile->id)); ?>
                                    <tr>
                                        <td class="p-3">
                                            <a href="<?php echo e(route('profiles.edit', $profile)); ?>" class="font-medium text-blue-700 hover:underline"><?php echo e($profile->name); ?></a>
                                            <p class="mt-1 text-xs text-slate-500"><?php echo e($profile->query); ?></p>
                                        </td>
                                        <td class="p-3"><?php echo e($activity ? number_format((int) data_get($activity->metadata, 'found', 0)) : '—'); ?></td>
                                        <td class="p-3"><?php echo e($activity ? number_format((int) data_get($activity->metadata, 'new_listings', 0)) : '—'); ?></td>
                                        <td class="p-3"><?php echo e(number_format($profile->listings_count)); ?></td>
                                        <td class="p-3"><?php echo e(number_format($profile->watching_listings_count)); ?></td>
                                        <td class="p-3"><?php echo e(number_format($profile->buy_listings_count)); ?></td>
                                        <td class="p-3 text-slate-600"><?php echo e($profile->last_synced_at?->timezone('Asia/Bishkek')->format('d.m.Y H:i') ?? 'Ещё не запускался'); ?></td>
                                        <td class="p-3">
                                            <?php if($profile->schedule_time): ?>
                                                <span class="text-slate-700"><?php echo e(substr($profile->schedule_time, 0, 5)); ?></span>
                                                <?php if($profile->schedule_interval_hours): ?>
                                                    <span class="text-xs text-slate-500"> каждые <?php echo e($profile->schedule_interval_hours); ?> ч</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-slate-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                'rounded-full px-2 py-1 text-xs font-medium',
                                                'bg-slate-100 text-slate-600' => ! $profile->active,
                                                'bg-amber-100 text-amber-900' => $profile->active && $isOverdue,
                                                'bg-emerald-100 text-emerald-800' => $profile->active && ! $isOverdue,
                                            ]); ?>">
                                                <?php echo e(! $profile->active ? 'Отключён' : ($isOverdue ? 'Просрочен' : 'По расписанию')); ?>

                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="9" data-column-empty-state class="p-8 text-center text-slate-500">Профилей поиска пока нет.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
<?php /**PATH /home/andrei/web/ebay/resources/views/search-summary.blade.php ENDPATH**/ ?>