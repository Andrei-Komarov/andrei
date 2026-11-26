<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Планировщик задач']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Планировщик задач']); ?>
    <div class="space-y-6">
        <?php if(session('success')): ?>
            <div class="rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="rounded bg-red-100 p-3 text-red-800">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <p><?php echo e($error); ?></p>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Планировщик задач</h1>
                <p class="text-sm text-slate-500">Мониторинг и управление фоновыми задачами импорта с eBay.</p>
            </header>

            <div class="flex items-start gap-4 rounded-lg border p-4"
                class="<?php echo \Illuminate\Support\Arr::toCssClasses(['border-emerald-200 bg-emerald-50' => $cronStatus['configured'], 'border-amber-200 bg-amber-50' => ! $cronStatus['configured']]); ?>">
                <span class="mt-0.5 text-xl" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-emerald-600' => $cronStatus['configured'], 'text-amber-600' => ! $cronStatus['configured']]); ?>">
                    <?php echo e($cronStatus['configured'] ? '✔' : '⚠'); ?>

                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-emerald-800' => $cronStatus['configured'], 'text-amber-800' => ! $cronStatus['configured']]); ?>">
                        <?php echo e($cronStatus['configured'] ? 'Cron настроен и работает' : 'Cron не обнаружен'); ?>

                    </p>
                    <p class="mt-1 text-xs text-slate-600">
                        <?php if($cronStatus['configured']): ?>
                            Команда <code class="rounded bg-white/60 px-1 font-mono">php artisan schedule:run</code> найдена в crontab.
                        <?php else: ?>
                            Добавьте <code class="rounded bg-white/60 px-1 font-mono">*</code> * * * * cd /path/to/project && php artisan schedule:run</code> в crontab сервера.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <form method="POST" action="<?php echo e(route('admin.scheduler.run-import')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        Запустить запланированные импорты
                    </button>
                </form>
            </div>
        </section>

        
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h2 class="text-lg font-bold">Конфигурация расписания</h2>
                <p class="text-sm text-slate-500">Настройки планировщика для команды <code class="rounded bg-slate-100 px-1 font-mono text-xs">ebay:import-laptops --scheduled</code>.</p>
            </header>

            <form method="POST" action="<?php echo e(route('admin.scheduler.schedule.update')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>

                <div>
                    <span class="block text-sm font-medium text-slate-700">Тип планировщика</span>
                    <div class="mt-1 flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="schedule_type" value="scheduled" <?php echo e($settings->schedule_type === 'cron' ? '' : 'checked'); ?> class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500" onchange="document.getElementById('scheduledSettings').classList.toggle('hidden', false); document.getElementById('cronInfo').classList.toggle('hidden', true);">
                            <span class="text-sm text-slate-700">Laravel Schedule</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="schedule_type" value="cron" <?php echo e($settings->schedule_type === 'cron' ? 'checked' : ''); ?> class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500" onchange="document.getElementById('scheduledSettings').classList.toggle('hidden', true); document.getElementById('cronInfo').classList.toggle('hidden', false);">
                            <span class="text-sm text-slate-700">Системный Cron</span>
                        </label>
                    </div>
                </div>

                <div id="scheduledSettings" class="<?php echo e($settings->schedule_type === 'cron' ? 'hidden' : ''); ?> space-y-4">
                    <div>
                        <label for="schedule_frequency" class="block text-sm font-medium text-slate-700">Частота выполнения</label>
                        <select name="schedule_frequency" id="schedule_frequency" class="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="everyMinute" <?php echo e($settings->schedule_frequency === 'everyMinute' ? 'selected' : ''); ?>>Каждую минуту</option>
                            <option value="everyFiveMinutes" <?php echo e($settings->schedule_frequency === 'everyFiveMinutes' ? 'selected' : ''); ?>>Каждые 5 минут</option>
                            <option value="everyTenMinutes" <?php echo e($settings->schedule_frequency === 'everyTenMinutes' ? 'selected' : ''); ?>>Каждые 10 минут</option>
                            <option value="everyFifteenMinutes" <?php echo e($settings->schedule_frequency === 'everyFifteenMinutes' ? 'selected' : ''); ?>>Каждые 15 минут</option>
                            <option value="everyThirtyMinutes" <?php echo e($settings->schedule_frequency === 'everyThirtyMinutes' ? 'selected' : ''); ?>>Каждые 30 минут</option>
                            <option value="hourly" <?php echo e($settings->schedule_frequency === 'hourly' ? 'selected' : ''); ?>>Каждый час</option>
                        </select>
                    </div>

                    <div>
                        <label for="schedule_timezone" class="block text-sm font-medium text-slate-700">Часовой пояс</label>
                        <select name="schedule_timezone" id="schedule_timezone" class="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <?php $__currentLoopData = $timezones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timezone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($timezone); ?>" <?php echo e($settings->schedule_timezone === $timezone ? 'selected' : ''); ?>><?php echo e($timezone); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="schedule_without_overlapping" id="schedule_without_overlapping" value="1" <?php echo e($settings->schedule_without_overlapping ? 'checked' : ''); ?> class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="schedule_without_overlapping" class="text-sm text-slate-700">Запускать без перекрытия (withoutOverlapping)</label>
                    </div>
                    <p class="text-xs text-slate-500">Если включено, новая задача не начнётся, если предыдущая ещё выполняется.</p>
                </div>

                <div id="cronInfo" class="<?php echo e($settings->schedule_type === 'cron' ? '' : 'hidden'); ?> rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-medium text-amber-800">Режим системного Cron</p>
                    <p class="mt-1 text-xs text-slate-600">
                        В этом режиме Laravel Schedule не планирует задачи автоматически. Запуск импорта осуществляется через системный cron сервера.
                    </p>
                    <p class="mt-2 text-xs text-slate-600">
                        Добавьте в crontab сервера команду для запуска импорта:
                    </p>
                    <code class="mt-1 block rounded bg-white/60 px-2 py-1 font-mono text-xs">* * * * * cd /path/to/project && php artisan ebay:import-laptops --scheduled</code>
                    <p class="mt-2 text-xs text-slate-500">
                        Или используйте кнопку «Запустить запланированные импорты» выше для ручного запуска.
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        Сохранить настройки
                    </button>
                </div>
            </form>
        </section>

        
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h2 class="text-lg font-bold">Профили с расписанием</h2>
                <p class="text-sm text-slate-500">Все активные поисковые профили и их параметры планирования.</p>
            </header>

            <?php if($profiles->isEmpty()): ?>
                <p class="text-sm text-slate-500">Поисковые профили не найдены.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
                                <th class="pb-2 pr-3 font-medium">ID</th>
                                <th class="pb-2 pr-3 font-medium">Профиль</th>
                                <th class="pb-2 pr-3 font-medium">Пользователь</th>
                                <th class="pb-2 pr-3 font-medium">Статус</th>
                                <th class="pb-2 pr-3 font-medium">Время старта</th>
                                <th class="pb-2 pr-3 font-medium">Интервал</th>
                                <th class="pb-2 pr-3 font-medium">Последний запуск</th>
                                <th class="pb-2 font-medium">Последняя синхр.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $profiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="py-2.5 pr-3 text-slate-500">#<?php echo e($profile['id']); ?></td>
                                    <td class="py-2.5 pr-3 font-medium"><?php echo e($profile['name']); ?></td>
                                    <td class="py-2.5 pr-3 text-slate-600"><?php echo e($profile['user_name']); ?></td>
                                    <td class="py-2.5 pr-3">
                                        <span class="rounded px-2 py-0.5 text-xs font-medium"
                                            class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bg-emerald-100 text-emerald-700' => $profile['active'], 'bg-slate-100 text-slate-500' => ! $profile['active']]); ?>">
                                            <?php echo e($profile['active'] ? 'Активен' : 'Неактивен'); ?>

                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-3 font-mono text-xs"><?php echo e($profile['schedule_time'] ?? '—'); ?></td>
                                    <td class="py-2.5 pr-3"><?php echo e($profile['schedule_interval_hours'] ? $profile['schedule_interval_hours'].' ч' : '—'); ?></td>
                                    <td class="py-2.5 pr-3 text-xs text-slate-500">
                                        <?php echo e($profile['last_scheduled_at'] ? $profile['last_scheduled_at']->diffForHumans() : '—'); ?>

                                    </td>
                                    <td class="py-2.5 text-xs text-slate-500">
                                        <?php echo e($profile['last_synced_at'] ? $profile['last_synced_at']->diffForHumans() : '—'); ?>

                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="py-1.5 pl-2">
                                        <form method="POST" action="<?php echo e(route('admin.scheduler.run-import')); ?>" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="profile_id" value="<?php echo e($profile['id']); ?>">
                                            <button type="submit" class="text-xs text-blue-700 hover:underline">
                                                Запустить импорт для этого профиля
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $attributes = $__attributesOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $component = $__componentOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__componentOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/scheduler/index.blade.php ENDPATH**/ ?>