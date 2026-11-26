<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Запущенные задачи']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Запущенные задачи']); ?>
    <div class="space-y-6">
        <?php if(session('success')): ?>
            <div class="rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="rounded bg-red-100 p-3 text-red-800"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Запущенные задачи</h1>
                    <p class="text-sm text-slate-500">Текущие запущенные задачи импорта с eBay. Зависшие задачи (более <?php echo e(\App\Models\SiteSetting::query()->value('ebay_stuck_threshold_minutes') ?? 180); ?> минут) отмечены красным. <a href="<?php echo e(route('admin.ebay.monitoring')); ?>" class="text-blue-700 hover:underline">Настроить</a>.</p>
                </div>
                <button type="button" class="rounded border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50" data-refresh-processes>
                    Обновить
                </button>
            </header>

            <div id="scheduler-processes" class="min-h-[2rem]">
                <p class="text-sm text-slate-500">Загрузка...</p>
            </div>
        </section>

        
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">Диспетчер расписания</h2>
                    <p class="text-sm text-slate-500">Фоновый процесс Laravel Schedule, который запускает импорт по расписанию.</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="<?php echo e(route('admin.scheduler.stop-schedule')); ?>" class="flex items-center gap-2">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="rounded border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                            Остановить
                        </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.scheduler.clear-mutex')); ?>" class="flex items-center gap-2"
                        data-confirm-dialog-form
                        data-confirm-button="Снять замок"
                        data-confirm-message="Перед снятием замка убедитесь, что импорт завершён или остановлен. Продолжить?">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="rounded border border-amber-300 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-50">
                            Снять замок
                        </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.scheduler.restart-schedule')); ?>" class="flex items-center gap-2">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">
                            Перезапустить
                        </button>
                    </form>
                </div>
            </header>

            <div id="schedule-dispatcher" class="min-h-[2rem]">
                <p class="text-sm text-slate-500">Загрузка...</p>
            </div>
        </section>

        
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">Лог планировщика</h2>
                    <p class="text-sm text-slate-500">Последние 50 строк из <code class="rounded bg-slate-100 px-1 font-mono text-xs">storage/logs/ebay-scheduler.log</code> (новые сверху).</p>
                </div>
                <form method="POST" action="<?php echo e(route('admin.scheduler.clear-log')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="rounded border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Очистить лог
                    </button>
                </form>
            </header>

            <?php if(! $schedulerLog['available']): ?>
                <p class="text-sm text-slate-500">Лог ещё не создан. Записи появятся после первого запуска планировщика.</p>
            <?php elseif(empty($schedulerLog['lines'])): ?>
                <p class="text-sm text-slate-500">Лог пуст.</p>
            <?php else: ?>
                <div class="max-h-96 overflow-y-auto rounded border bg-slate-900 p-3 font-mono text-xs leading-relaxed text-slate-200">
                    <?php $__currentLoopData = $schedulerLog['lines']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="whitespace-pre-wrap"><?php echo e($line); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Kill confirmation modal -->
    <dialog id="kill-confirm-dialog" class="rounded-xl bg-white p-0 shadow-2xl backdrop:bg-slate-800/50 max-w-md w-full" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path>
                        <line x1="12" x2="12" y1="9" y2="13"></line>
                        <line x1="12" x2="12.01" y1="17" y2="17"></line>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Завершить процесс</h2>
            </div>
            <p class="text-sm text-slate-600 mb-1" id="kill-confirm-message"></p>
            <p class="text-xs text-red-600 mb-6">Это действие нельзя отменить. Текущий импорт может быть прерван с потерей данных.</p>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" id="kill-cancel-btn">
                    Отмена
                </button>
                <button type="button" class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700" id="kill-confirm-btn">
                    Завершить
                </button>
            </div>
        </div>
    </dialog>

    <script>
    (function() {
        const container = document.getElementById('scheduler-processes');
        const dispatcherContainer = document.getElementById('schedule-dispatcher');
        const refreshBtn = document.querySelector('[data-refresh-processes]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const killUrl = '<?php echo e(route('admin.scheduler.kill-process')); ?>';
        const dialog = document.getElementById('kill-confirm-dialog');
        const messageEl = document.getElementById('kill-confirm-message');
        const cancelBtn = document.getElementById('kill-cancel-btn');
        const confirmBtn = document.getElementById('kill-confirm-btn');
        let pendingPid = null;
        let pendingProfileId = null;

        function openKillDialog(pid, profileId) {
            pendingPid = pid;
            pendingProfileId = profileId || null;
            messageEl.textContent = 'Вы уверены, что хотите принудительно завершить процесс #' + pid + '?';
            dialog.showModal();
        }

        function closeKillDialog() {
            dialog.close();
            pendingPid = null;
            pendingProfileId = null;
        }

        function executeKill() {
            if (pendingPid === null) return;
            const pid = pendingPid;
            const profileId = pendingProfileId;
            closeKillDialog();
            let body = 'pid=' + pid;
            if (profileId) {
                body += '&profile_id=' + profileId;
            }
            fetch(killUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: body
            }).then(() => location.reload());
        }

        cancelBtn.addEventListener('click', closeKillDialog);
        confirmBtn.addEventListener('click', executeKill);

        function loadProcesses() {
            fetch('<?php echo e(route('admin.scheduler.processes')); ?>', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(res => {
                    if (!res.ok) {
                        if (res.headers.get('content-type')?.includes('application/json')) {
                            return res.json().then(data => { throw new Error(data.error || 'Ошибка сервера'); });
                        }
                        throw new Error('Ошибка сервера (' + res.status + ')');
                    }
                    return res.json();
                })
                .then(processes => {
                    if (processes.length === 0) {
                        container.innerHTML = '<p class="text-sm text-slate-500">Нет активных процессов импорта.</p>';
                        return;
                    }

                    let html = '<div class="space-y-3">';
                    processes.forEach(p => {
                        const typeLabel = p.type === 'scheduled' ? 'По расписанию' : p.type === 'orphan' ? 'Зависший' : 'Ручной';
                        const typeClass = p.type === 'orphan' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700';
                        const stuckBadge = p.is_stuck ? '<span class="ml-2 rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Завис</span>' : '';
                        const startedLabel = p.started_at ? p.started_at : 'неизвестно';
                        const profileName = p.profile_name ? '<p class="text-xs font-medium text-slate-700">Профиль: ' + p.profile_name + '</p>' : '';
                        const profileId = p.profile_id || '';

                        html += `
                            <div class="flex items-center justify-between rounded-lg border p-3"
                                ${p.is_stuck ? 'class="border-red-200 bg-red-50"' : 'class="border-slate-200 bg-slate-50"'}>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full ${p.is_stuck ? 'bg-red-400' : 'bg-emerald-400'} opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 ${p.is_stuck ? 'bg-red-500' : 'bg-emerald-500'}"></span>
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium">
                                            PID: ${p.pid}
                                            <span class="rounded px-2 py-0.5 text-xs font-medium ${typeClass}">${typeLabel}</span>
                                            ${stuckBadge}
                                        </p>
                                        ${profileName}
                                        <p class="text-xs text-slate-500">
                                            Запущен: ${startedLabel} · Работает: ${p.elapsed}
                                        </p>
                                    </div>
                                </div>
                                <button type="button"
                                    class="rounded bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                    data-kill-process="${p.pid}"
                                    data-kill-profile="${profileId}">
                                    Завершить
                                </button>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;

                    // Attach event listeners to kill buttons
                    container.querySelectorAll('[data-kill-process]').forEach(btn => {
                        btn.addEventListener('click', () => openKillDialog(btn.dataset.killProcess, btn.dataset.killProfile));
                    });
                })
                .catch(err => {
                    container.innerHTML = '<p class="text-sm text-red-600">Ошибка загрузки: ' + err.message + '</p>';
                });
        }

        function loadDispatcher() {
            fetch('<?php echo e(route('admin.scheduler.dispatcher')); ?>', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(res => {
                    if (!res.ok) {
                        if (res.headers.get('content-type')?.includes('application/json')) {
                            return res.json().then(data => { throw new Error(data.error || 'Ошибка сервера'); });
                        }
                        throw new Error('Ошибка сервера (' + res.status + ')');
                    }
                    return res.json();
                })
                .then(dispatcher => {
                    if (!dispatcher) {
                        dispatcherContainer.innerHTML = '<p class="text-sm text-slate-500">Диспетчер расписания не запущен.</p>';
                        return;
                    }

                    const startedLabel = dispatcher.started_at ? dispatcher.started_at : 'неизвестно';
                    dispatcherContainer.innerHTML = `
                        <div class="flex items-center justify-between rounded-lg border border-purple-200 bg-purple-50 p-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full bg-purple-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-purple-500"></span>
                                </span>
                                <div>
                                    <p class="text-sm font-medium">
                                        PID: ${dispatcher.pid}
                                        <span class="ml-2 rounded bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Диспетчер расписания</span>
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Запущен: ${startedLabel} · Работает: ${dispatcher.elapsed}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .catch(err => {
                    dispatcherContainer.innerHTML = '<p class="text-sm text-red-600">Ошибка загрузки: ' + err.message + '</p>';
                });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                loadProcesses();
                loadDispatcher();
            });
        }

        // Load on page load
        loadProcesses();
        loadDispatcher();

        // Auto-refresh every 30 seconds
        setInterval(() => {
            loadProcesses();
            loadDispatcher();
        }, 30000);
    })();
    </script>
    <?php echo $__env->make('partials.confirm-dialog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/scheduler/processes.blade.php ENDPATH**/ ?>