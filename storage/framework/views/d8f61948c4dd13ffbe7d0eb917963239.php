<!doctype html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <?php if (isset($component)) { $__componentOriginal82e3f864bb766fbb95cb0a10b750823c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal82e3f864bb766fbb95cb0a10b750823c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.favicon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('favicon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal82e3f864bb766fbb95cb0a10b750823c)): ?>
<?php $attributes = $__attributesOriginal82e3f864bb766fbb95cb0a10b750823c; ?>
<?php unset($__attributesOriginal82e3f864bb766fbb95cb0a10b750823c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82e3f864bb766fbb95cb0a10b750823c)): ?>
<?php $component = $__componentOriginal82e3f864bb766fbb95cb0a10b750823c; ?>
<?php unset($__componentOriginal82e3f864bb766fbb95cb0a10b750823c); ?>
<?php endif; ?>
        <title><?php echo e($title); ?> — Управление eBay Finder</title>
        <?php echo $__env->make('partials.theme-bootstrap', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <header class="border-b border-slate-700 bg-slate-900 text-white shadow-sm">
            <div class="mx-auto flex max-w-[1857px] flex-wrap items-center justify-between gap-4 px-4 py-3 md:px-6">
                <a href="<?php echo e(route('admin.index')); ?>" class="flex items-center gap-3" aria-label="Админ-панель">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold">LF</span>
                    <span>
                        <span class="block text-base font-bold leading-tight">Finder</span>
                        <span class="block text-xs text-slate-300">Административная панель</span>
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-semibold"><?php echo e(auth('admin')->user()->name); ?></p>
                        <p class="text-xs text-slate-300"><?php echo e(auth('admin')->user()->email); ?></p>
                    </div>
                    <a href="<?php echo e(route('listings.index')); ?>" class="rounded border border-slate-600 px-3 py-2 text-xs font-medium hover:bg-slate-800" aria-label="Перейти на сайт">Сайт</a>
                    <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="rounded border border-slate-600 px-3 py-2 text-xs hover:bg-slate-800" type="submit">Выйти</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto grid max-w-[1857px] gap-6 p-4 md:grid-cols-[15rem_minmax(0,1fr)] md:p-6">
            <aside class="h-fit rounded-xl bg-white p-4 shadow-sm" aria-label="Меню администратора">
                <nav class="space-y-6">
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Настройки</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.index'), 'text-slate-700' => ! request()->routeIs('admin.index')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.index') ? 'page' : 'false'); ?>">Пользователи и роли</a></li>
                            <li><a href="<?php echo e(route('admin.settings')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.settings'), 'text-slate-700' => ! request()->routeIs('admin.settings')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.settings') ? 'page' : 'false'); ?>">Настройки сайта</a></li>
                            <li><a href="<?php echo e(route('admin.backup.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.backup.*'), 'text-slate-700' => ! request()->routeIs('admin.backup.*')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.backup.*') ? 'page' : 'false'); ?>">Резервное копирование</a></li>
                            <li><a href="<?php echo e(route('admin.excluded-words.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.excluded-words.*'), 'text-slate-700' => ! request()->routeIs('admin.excluded-words.*')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.excluded-words.*') ? 'page' : 'false'); ?>">Слова исключения</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">SEO</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.seo.seo')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.seo.seo'), 'text-slate-700' => ! request()->routeIs('admin.seo.seo')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.seo.seo') ? 'page' : 'false'); ?>">SEO-настройки</a></li>
                            <li><a href="<?php echo e(route('admin.seo.advanced')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.seo.advanced'), 'text-slate-700' => ! request()->routeIs('admin.seo.advanced')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.seo.advanced') ? 'page' : 'false'); ?>">Расширенные SEO</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">eBay настройки</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.ebay.account')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.ebay.account'), 'text-slate-700' => ! request()->routeIs('admin.ebay.account')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.ebay.account') ? 'page' : 'false'); ?>">Настройки eBay аккаунта</a></li>
                            <li><a href="<?php echo e(route('admin.ebay.delays')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.ebay.delays'), 'text-slate-700' => ! request()->routeIs('admin.ebay.delays')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.ebay.delays') ? 'page' : 'false'); ?>">Задержки eBay API</a></li>
                            <li><a href="<?php echo e(route('admin.ebay.advanced')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.ebay.advanced'), 'text-slate-700' => ! request()->routeIs('admin.ebay.advanced')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.ebay.advanced') ? 'page' : 'false'); ?>">Расширенные настройки eBay</a></li>
                            <li><a href="<?php echo e(route('admin.ebay.extra')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.ebay.extra'), 'text-slate-700' => ! request()->routeIs('admin.ebay.extra')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.ebay.extra') ? 'page' : 'false'); ?>">Дополнительные настройки eBay</a></li>
                            <li><a href="<?php echo e(route('admin.ebay.monitoring')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.ebay.monitoring'), 'text-slate-700' => ! request()->routeIs('admin.ebay.monitoring')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.ebay.monitoring') ? 'page' : 'false'); ?>">Мониторинг импорта</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Безопасность</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.security.security')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.security.security'), 'text-slate-700' => ! request()->routeIs('admin.security.security')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.security.security') ? 'page' : 'false'); ?>">Безопасность</a></li>
                            <li><a href="<?php echo e(route('admin.security.cors')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.security.cors'), 'text-slate-700' => ! request()->routeIs('admin.security.cors')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.security.cors') ? 'page' : 'false'); ?>">CORS и безопасность API</a></li>
                            <li><a href="<?php echo e(route('admin.security.advanced')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.security.advanced'), 'text-slate-700' => ! request()->routeIs('admin.security.advanced')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.security.advanced') ? 'page' : 'false'); ?>">Расширенная безопасность</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Логирование</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.logs.user-activity')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.logs.user-activity'), 'text-slate-700' => ! request()->routeIs('admin.logs.user-activity')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.logs.user-activity') ? 'page' : 'false'); ?>">Действия пользователей</a></li>
                            <li><a href="<?php echo e(route('admin.logs.system-errors')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.logs.system-errors'), 'text-slate-700' => ! request()->routeIs('admin.logs.system-errors')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.logs.system-errors') ? 'page' : 'false'); ?>">Системные ошибки</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Планировщик задач</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.scheduler.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.scheduler.index'), 'text-slate-700' => ! request()->routeIs('admin.scheduler.index')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.scheduler.index') ? 'page' : 'false'); ?>">Планировщик задач</a></li>
                            <li><a href="<?php echo e(route('admin.scheduler.processes-page')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.scheduler.processes-page'), 'text-slate-700' => ! request()->routeIs('admin.scheduler.processes-page')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.scheduler.processes-page') ? 'page' : 'false'); ?>">Запущенные задачи</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Финансы и аналитика</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.profitability-parts.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.profitability-parts.*'), 'text-slate-700' => ! request()->routeIs('admin.profitability-parts.*')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.profitability-parts.*') ? 'page' : 'false'); ?>">Запчасти для расчёта</a></li>
                        </ul>
                    </section>
                    <section>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Аккаунт</h2>
                        <ul class="space-y-1 text-sm">
                            <li><a href="<?php echo e(route('admin.account.profile')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.account.profile'), 'text-slate-700' => ! request()->routeIs('admin.account.profile')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.account.profile') ? 'page' : 'false'); ?>">Профиль</a></li>
                            <li><a href="<?php echo e(route('admin.account.password')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.account.password'), 'text-slate-700' => ! request()->routeIs('admin.account.password')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.account.password') ? 'page' : 'false'); ?>">Пароль</a></li>
                            <li><a href="<?php echo e(route('admin.account.notifications')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.account.notifications'), 'text-slate-700' => ! request()->routeIs('admin.account.notifications')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.account.notifications') ? 'page' : 'false'); ?>">Уведомления</a></li>
                            <li><a href="<?php echo e(route('admin.account.security')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('admin.account.security'), 'text-slate-700' => ! request()->routeIs('admin.account.security')]); ?>" aria-current="<?php echo e(request()->routeIs('admin.account.security') ? 'page' : 'false'); ?>">Безопасность</a></li>
                        </ul>
                    </section>
                </nav>
            </aside>
            <section class="min-w-0"><?php echo e($slot); ?></section>
        </main>
    </body>
</html>
<?php /**PATH /home/andrei/web/ebay/resources/views/components/admin-layout.blade.php ENDPATH**/ ?>