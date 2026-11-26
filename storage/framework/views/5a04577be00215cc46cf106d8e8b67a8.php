<?php
    $appearanceSettings = \App\Models\SiteSetting::query()->first();
    $siteName = $appearanceSettings->seo_site_name ?? 'Finder';
    $siteSubtitle = $appearanceSettings->appearance_site_subtitle ?? 'Поиск товаров на eBay';
    $logoPath = $appearanceSettings->appearance_logo_path;
?>
<header class="border-b border-slate-200 bg-white shadow-sm">
    <div class="mx-auto flex max-w-[1857px] flex-wrap items-center justify-between gap-4 px-4 py-3 md:px-6">
        <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-3" aria-label="<?php echo e($siteName); ?> — главная">
            <?php if($logoPath): ?>
                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($logoPath)); ?>" alt="<?php echo e($siteName); ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="13" rx="2"></rect>
                        <path d="M2 21h20M8 17l-1 4m9-4 1 4M8 9h8m-8 3h5"></path>
                    </svg>
                </span>
            <?php endif; ?>
            <span>
                <span class="block text-base font-bold leading-tight text-slate-900"><?php echo e($siteName); ?></span>
                <span class="block text-xs text-slate-500"><?php echo e($siteSubtitle); ?></span>
            </span>
        </a>

        <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700" aria-hidden="true">
                <?php if(auth()->guard()->check()): ?>
                    <?php echo e(mb_strtoupper(mb_substr(auth()->user()->name, 0, 1))); ?>

                <?php else: ?>
                    ?
                <?php endif; ?>
            </span>
            <?php if(auth()->guard()->check()): ?>
                <details class="group relative" data-notification-menu>
                    <summary
                        class="relative flex h-9 w-9 shrink-0 cursor-pointer list-none items-center justify-center rounded-lg text-slate-600 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Уведомления. Непрочитанных: <?php echo e($unreadListingNotificationCount); ?>. Открыть список"
                        title="Показать уведомления"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4" />
                        </svg>
                        <?php if($unreadListingNotificationCount > 0): ?>
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white" aria-hidden="true" data-unread-notification-count>
                                <?php echo e($unreadListingNotificationCount > 99 ? '99+' : $unreadListingNotificationCount); ?>

                            </span>
                        <?php endif; ?>
                    </summary>
                    <section class="absolute right-0 top-full z-50 hidden w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900 shadow-xl group-hover:block group-focus-within:block group-open:block" aria-label="Последние уведомления">
                        <header class="flex items-center justify-between gap-3 border-b border-slate-200 p-3">
                            <h2 class="font-semibold">Новые товары</h2>
                            <a href="<?php echo e(route('notifications.index')); ?>" class="shrink-0 text-sm font-medium text-blue-700 hover:underline">Все новости</a>
                        </header>
                        <div class="max-h-96 divide-y divide-slate-200 overflow-y-auto">
                            <?php $__empty_1 = true; $__currentLoopData = $unreadListingNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <article class="p-3 hover:bg-slate-50">
                                    <a
                                        href="<?php echo e(filled($notification->data['item_url'] ?? null) ? $notification->data['item_url'] : route('notifications.index')); ?>"
                                        <?php if(filled($notification->data['item_url'] ?? null)): ?> target="_blank" rel="noopener" <?php endif; ?>
                                        class="block font-medium text-blue-700 hover:underline"
                                    >
                                        <?php echo e($notification->data['title'] ?? 'Новое уведомление'); ?>

                                    </a>
                                    <p class="mt-1 text-xs text-slate-600">
                                        <?php echo e($notification->data['profile_name'] ?? 'Профиль поиска'); ?>

                                        <?php if(isset($notification->data['total_price'], $notification->data['currency'])): ?>
                                            · <?php echo e($notification->data['currency']); ?> <?php echo e(number_format((float) $notification->data['total_price'], 2)); ?>

                                        <?php endif; ?>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500"><?php echo e($notification->created_at->format('d.m.Y H:i')); ?></p>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <p class="p-4 text-center text-sm text-slate-500">Новых уведомлений пока нет.</p>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e(route('notifications.index')); ?>" class="block border-t border-slate-200 p-3 text-center text-sm font-medium text-blue-700 hover:bg-slate-50">
                            Открыть все новости
                        </a>
                    </section>
                </details>
            <?php endif; ?>
            <div class="min-w-0">
                <?php if(auth()->guard()->check()): ?>
                    <p class="max-w-48 truncate text-sm font-semibold text-slate-900"><?php echo e(auth()->user()->name); ?></p>
                    <p class="max-w-48 truncate text-xs text-slate-500"><?php echo e(auth()->user()->email); ?></p>
                <?php else: ?>
                    <?php if(request()->routeIs('login', 'register')): ?>
                        <span class="text-sm font-medium text-slate-700">Добро пожаловать</span>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" class="text-sm font-medium text-blue-700 hover:underline">Войти</a>
                        <a href="<?php echo e(route('register')); ?>" class="block text-xs text-slate-500 hover:underline">Регистрация</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if(auth()->guard()->check()): ?>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="text-xs text-slate-500 hover:text-red-700" type="submit">Выйти</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php /**PATH /home/andrei/web/ebay/resources/views/partials/sitebar.blade.php ENDPATH**/ ?>