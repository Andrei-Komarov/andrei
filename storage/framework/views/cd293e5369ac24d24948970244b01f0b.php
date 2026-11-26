<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Мониторинг импорта']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Мониторинг импорта']); ?>
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <?php if(session('success')): ?>
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Мониторинг импорта</h1>
            <p class="text-sm text-slate-500">Настройки определения зависших задач импорта с eBay и задержки при ошибках.</p>
        </header>

        <form method="POST" action="<?php echo e(route('admin.ebay.monitoring.update')); ?>" class="space-y-6">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label for="ebay_stuck_threshold_minutes" class="block text-sm font-medium">Порог зависших задач (минуты)</label>
                <input
                    id="ebay_stuck_threshold_minutes"
                    name="ebay_stuck_threshold_minutes"
                    type="number"
                    min="10"
                    max="1440"
                    step="1"
                    value="<?php echo e(old('ebay_stuck_threshold_minutes', $siteSettings->ebay_stuck_threshold_minutes ?? 180)); ?>"
                    class="mt-1 w-48 rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">
                    Задачи, работающие дольше указанного времени, будут помечены как зависшие.
                    Минимум: 10 минут. Максимум: 1440 минут (24 часа). По умолчанию: 180 минут (3 часа).
                </p>
            </div>

            <?php $__errorArgs = ['ebay_stuck_threshold_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-sm text-red-700"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <div>
                <label for="ebay_auto_kill_hung" class="flex items-center gap-2">
                    <input
                        id="ebay_auto_kill_hung"
                        name="ebay_auto_kill_hung"
                        type="checkbox"
                        value="1"
                        <?php echo e(old('ebay_auto_kill_hung', ($siteSettings->ebay_auto_kill_hung ?? true) ? 'checked' : '')); ?>

                        class="h-4 w-4 rounded border text-blue-700"
                    >
                    <span class="text-sm font-medium">Автоматически завершать зависшие процессы</span>
                </label>
                <p class="mt-1 text-xs text-slate-500">
                    Если процесс парсинга работает дольше порога зависших задач, он будет принудительно остановлен,
                    и планировщик перейдёт к следующему профилю по расписанию.
                </p>
            </div>

            <div class="border-t border-slate-200 pt-6">
                <h2 class="text-lg font-semibold">Задержка при ошибке 429</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Когда eBay API возвращает ошибку «Too Many Requests» (429), планировщик будет ждать указанное время перед запуском следующей задачи.
                </p>

                <div class="mt-4">
                    <label for="ebay_429_delay_minutes" class="block text-sm font-medium">Задержка после ошибки 429 (минуты)</label>
                    <input
                        id="ebay_429_delay_minutes"
                        name="ebay_429_delay_minutes"
                        type="number"
                        min="1"
                        max="1440"
                        step="1"
                        value="<?php echo e(old('ebay_429_delay_minutes', $siteSettings->ebay_429_delay_minutes ?? 60)); ?>"
                        class="mt-1 w-48 rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        Время ожидания в минутах перед запуском следующей запланированной задачи после получения ошибки 429 от eBay API.
                        Минимум: 1 минута. Максимум: 1440 минут (24 часа). По умолчанию: 60 минут.
                    </p>
                </div>

                <?php $__errorArgs = ['ebay_429_delay_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-sm text-red-700"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                Сохранить настройки
            </button>
        </form>
    </section>

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Дневной лимит eBay API</h1>
            <p class="text-sm text-slate-500">Настройка порога остановки парсинга для предотвращения исчерпания дневного лимита запросов.</p>
        </header>

        <form method="POST" action="<?php echo e(route('admin.ebay.api-limit.update')); ?>" class="space-y-6">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label for="ebay_api_limit_threshold_percent" class="block text-sm font-medium">Порог остановки парсинга (%)</label>
                <input
                    id="ebay_api_limit_threshold_percent"
                    name="ebay_api_limit_threshold_percent"
                    type="number"
                    min="50"
                    max="99"
                    step="1"
                    value="<?php echo e(old('ebay_api_limit_threshold_percent', $siteSettings->ebay_api_limit_threshold_percent ?? 90)); ?>"
                    class="mt-1 w-48 rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">
                    Парсинг будет остановлен при достижении указанного процента от дневного лимита (5 000 запросов).
                    Например, при 90% парсинг остановится на 4 500 запросах, оставив запас в 500 запросов.
                    Минимум: 50%. Максимум: 99%. По умолчанию: 90%.
                </p>
            </div>

            <?php $__errorArgs = ['ebay_api_limit_threshold_percent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-sm text-red-700"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                Сохранить настройки
            </button>
        </form>
    </section>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/ebay/monitoring.blade.php ENDPATH**/ ?>