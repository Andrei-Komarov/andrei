<aside class="h-fit rounded-xl bg-white p-4 shadow-sm" aria-label="Главное меню">
    <nav class="space-y-6">
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Товары</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="<?php echo e(route('listings.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.index'), 'text-slate-700' => ! request()->routeIs('listings.index')]); ?>">Список товаров</a></li>
                <li><a href="<?php echo e(route('listings.excluded')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.excluded'), 'text-slate-700' => ! request()->routeIs('listings.excluded')]); ?>">Исключённый товар</a></li>
                <li>
                    <a href="<?php echo e(route('notifications.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex items-center justify-between rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('notifications.*'), 'text-slate-700' => ! request()->routeIs('notifications.*')]); ?>">
                        <span>Новые товары</span>
                        <?php $unreadCount = auth()->user()->unreadNotifications()->count(); ?>
                        <?php if($unreadCount > 0): ?>
                            <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-center text-xs font-bold text-white"><?php echo e($unreadCount); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="<?php echo e(route('listings.buy')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.buy'), 'text-slate-700' => ! request()->routeIs('listings.buy')]); ?>">Список покупок</a></li>
                <li><a href="<?php echo e(route('listings.watchlist')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.watchlist'), 'text-slate-700' => ! request()->routeIs('listings.watchlist')]); ?>">Список наблюдения</a></li>
                <li><a href="<?php echo e(route('listings.price-changes')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.price-changes'), 'text-slate-700' => ! request()->routeIs('listings.price-changes')]); ?>">Изменение цен</a></li>
                <li><a href="<?php echo e(route('listings.deleted')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.deleted'), 'text-slate-700' => ! request()->routeIs('listings.deleted')]); ?>">Удалённые товары</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Поисковые запросы</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="<?php echo e(route('profiles.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profiles.index', 'profiles.edit'), 'text-slate-700' => ! request()->routeIs('profiles.index', 'profiles.edit')]); ?>">Список профилей</a></li>
                <li><a href="<?php echo e(route('search-summary')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('search-summary'), 'text-slate-700' => ! request()->routeIs('search-summary')]); ?>">Сводка по поиску</a></li>
                <li><a href="<?php echo e(route('profiles.history')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profiles.history'), 'text-slate-700' => ! request()->routeIs('profiles.history')]); ?>">История поиска</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Финансы</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="<?php echo e(route('profitability.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profitability.*'), 'text-slate-700' => ! request()->routeIs('profitability.*')]); ?>">Рентабельность</a></li>
                <li><a href="<?php echo e(route('analytics.index')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('analytics.*'), 'text-slate-700' => ! request()->routeIs('analytics.*')]); ?>">Статистика и аналитика</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Аккаунт</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="<?php echo e(route('account.profile')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.profile'), 'text-slate-700' => ! request()->routeIs('account.profile')]); ?>">Профиль</a></li>
                <li><a href="<?php echo e(route('account.password')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.password'), 'text-slate-700' => ! request()->routeIs('account.password')]); ?>">Пароль</a></li>
                <li><a href="<?php echo e(route('account.notifications')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.notifications'), 'text-slate-700' => ! request()->routeIs('account.notifications')]); ?>">Уведомления</a></li>
                <li><a href="<?php echo e(route('account.security')); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.security'), 'text-slate-700' => ! request()->routeIs('account.security')]); ?>">Безопасность</a></li>
            </ul>
        </section>
        <ul class="space-y-1 border-t pt-4 text-sm">
            <li><span class="block cursor-not-allowed rounded px-3 py-2 text-slate-400" aria-disabled="true" title="Раздел пока недоступен">Вопросы и ответы</span></li>
            <li><span class="block cursor-not-allowed rounded px-3 py-2 text-slate-400" aria-disabled="true" title="Раздел пока недоступен">Написать администратору</span></li>
        </ul>
    </nav>
</aside>
<?php /**PATH /home/andrei/web/ebay/resources/views/partials/sidebar.blade.php ENDPATH**/ ?>