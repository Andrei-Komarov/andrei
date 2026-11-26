<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>История поиска — eBay Finder</title>
        <?php echo $__env->make('partials.theme-bootstrap', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <?php echo $__env->make('partials.sitebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="min-w-0">
            <section class="rounded-xl bg-white shadow-sm">
                <header class="border-b border-slate-200 p-4">
                    <h1 class="text-2xl font-bold">История поиска</h1>
                    <p class="text-sm text-slate-500">Действия с профилями поиска и результаты парсинга</p>
                </header>
                <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'profile-history'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="overflow-x-auto">
                    <table class="data-table text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'date', 'sortKey' => 'date', 'label' => 'Дата и время', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <th class="p-3 font-medium text-slate-500">Время парсинга</th>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'event', 'sortKey' => 'event', 'label' => 'Событие', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'profile', 'sortKey' => 'profile', 'label' => 'Профиль', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'query', 'sortKey' => 'query', 'label' => 'Поисковый запрос', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'user', 'sortKey' => 'user', 'label' => 'Пользователь', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <th class="p-3 font-medium text-slate-500">PID</th>
                                <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'details', 'sortKey' => 'details', 'label' => 'Подробности', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="p-3 text-slate-600"><?php echo e($activity->created_at->format('d.m.Y H:i:s')); ?></td>
                                    <td class="p-3 text-slate-600"><?php echo e($activity->parsing_duration ?? '—'); ?></td>
                                    <td class="p-3">
                                        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-800' => in_array($activity->action, ['created', 'parse_completed']),
                                            'bg-blue-100 text-blue-800' => $activity->action === 'updated' || $activity->action === 'parse_started',
                                            'bg-red-100 text-red-800' => in_array($activity->action, ['deleted', 'parse_failed', 'parse_killed']),
                                        ]); ?>">
                                            <?php echo e(match ($activity->action) {
                                                'created' => 'Создание',
                                                'updated' => 'Изменение',
                                                'deleted' => 'Удаление',
                                                'parse_started' => 'Парсинг запущен',
                                                'parse_completed' => 'Парсинг завершён',
                                                'parse_failed' => 'Ошибка парсинга',
                                                'parse_killed' => 'Закрыт принудительно',
                                                default => $activity->action,
                                            }); ?>

                                        </span>
                                    </td>
                                    <td class="p-3 font-medium"><?php echo e($activity->profile_name); ?></td>
                                    <td class="p-3 text-slate-600"><?php echo e($activity->searchProfile->query ?? '—'); ?></td>
                                    <td class="p-3 text-slate-600"><?php echo e($activity->user?->name ?? 'Система / гость'); ?></td>
                                    <td class="p-3 font-mono text-sm text-slate-600"><?php echo e($activity->pid ?? '—'); ?></td>
                                    <td class="max-w-xl p-3 text-slate-700">
                                        <?php echo e($activity->description); ?>

                                        <?php if(! empty($activity->metadata['changed_fields'])): ?>
                                            <div class="mt-1 text-xs text-slate-500">Изменены поля: <?php echo e(implode(', ', $activity->metadata['changed_fields'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" data-column-empty-state class="p-8 text-center text-slate-500">История пока пуста. Здесь появятся действия с профилями и итоги парсинга.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4"><?php echo e($activities->links()); ?></div>
            </section>
            </div>
        </main>
    </body>
</html>
<?php /**PATH /home/andrei/web/ebay/resources/views/profiles/history.blade.php ENDPATH**/ ?>