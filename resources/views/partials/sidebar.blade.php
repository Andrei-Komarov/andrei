<aside class="h-fit rounded-xl bg-white p-4 shadow-sm" aria-label="Главное меню">
    <nav class="space-y-6">
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Товары</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="{{ route('listings.index') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.index'), 'text-slate-700' => ! request()->routeIs('listings.index')])>Список товаров</a></li>
                <li><a href="{{ route('listings.excluded') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.excluded'), 'text-slate-700' => ! request()->routeIs('listings.excluded')])>Исключённый товар</a></li>
                <li>
                    <a href="{{ route('notifications.index') }}" @class(['flex items-center justify-between rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('notifications.*'), 'text-slate-700' => ! request()->routeIs('notifications.*')])>
                        <span>Новые товары</span>
                        @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                        @if ($unreadCount > 0)
                            <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-center text-xs font-bold text-white">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
                <li><a href="{{ route('listings.buy') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.buy'), 'text-slate-700' => ! request()->routeIs('listings.buy')])>Список покупок</a></li>
                <li><a href="{{ route('listings.watchlist') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.watchlist'), 'text-slate-700' => ! request()->routeIs('listings.watchlist')])>Список наблюдения</a></li>
                <li><a href="{{ route('listings.price-changes') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.price-changes'), 'text-slate-700' => ! request()->routeIs('listings.price-changes')])>Изменение цен</a></li>
                <li><a href="{{ route('listings.deleted') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('listings.deleted'), 'text-slate-700' => ! request()->routeIs('listings.deleted')])>Удалённые товары</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Поисковые запросы</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="{{ route('profiles.index') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profiles.index', 'profiles.edit'), 'text-slate-700' => ! request()->routeIs('profiles.index', 'profiles.edit')])>Список профилей</a></li>
                <li><a href="{{ route('search-summary') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('search-summary'), 'text-slate-700' => ! request()->routeIs('search-summary')])>Сводка по поиску</a></li>
                <li><a href="{{ route('profiles.history') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profiles.history'), 'text-slate-700' => ! request()->routeIs('profiles.history')])>История поиска</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Финансы</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="{{ route('profitability.index') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('profitability.*'), 'text-slate-700' => ! request()->routeIs('profitability.*')])>Рентабельность</a></li>
                <li><a href="{{ route('analytics.index') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('analytics.*'), 'text-slate-700' => ! request()->routeIs('analytics.*')])>Статистика и аналитика</a></li>
            </ul>
        </section>
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Аккаунт</h2>
            <ul class="space-y-1 text-sm">
                <li><a href="{{ route('account.profile') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.profile'), 'text-slate-700' => ! request()->routeIs('account.profile')])>Профиль</a></li>
                <li><a href="{{ route('account.password') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.password'), 'text-slate-700' => ! request()->routeIs('account.password')])>Пароль</a></li>
                <li><a href="{{ route('account.notifications') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.notifications'), 'text-slate-700' => ! request()->routeIs('account.notifications')])>Уведомления</a></li>
                <li><a href="{{ route('account.security') }}" @class(['block rounded px-3 py-2 hover:bg-slate-100', 'bg-blue-50 font-medium text-blue-700' => request()->routeIs('account.security'), 'text-slate-700' => ! request()->routeIs('account.security')])>Безопасность</a></li>
            </ul>
        </section>
        <ul class="space-y-1 border-t pt-4 text-sm">
            <li><span class="block cursor-not-allowed rounded px-3 py-2 text-slate-400" aria-disabled="true" title="Раздел пока недоступен">Вопросы и ответы</span></li>
            <li><span class="block cursor-not-allowed rounded px-3 py-2 text-slate-400" aria-disabled="true" title="Раздел пока недоступен">Написать администратору</span></li>
        </ul>
    </nav>
</aside>
