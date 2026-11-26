<x-admin-layout title="Настройки">
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Вид валюты</h1>
                <p class="text-sm text-slate-500">Выберите валюту для новых расчётов рентабельности. Цены eBay остаются в исходной валюте объявления.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.profitability.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <label for="profitability_currency" class="block text-sm font-medium">
                    Валюта расчёта
                    <select id="profitability_currency" name="currency" data-currency-select class="mt-1 w-full rounded border p-2 sm:max-w-sm">
                        <option value="KGS" @selected(old('currency', $siteSettings->profitability_currency) === 'KGS')>Сомы (KGS)</option>
                        <option value="USD" @selected(old('currency', $siteSettings->profitability_currency) === 'USD')>Доллары (USD)</option>
                    </select>
                </label>
                <label for="exchange_rate" data-exchange-rate-field @class(['block', 'text-sm', 'font-medium', 'hidden' => old('currency', $siteSettings->profitability_currency) !== 'USD'])>
                    Курс обмена: сом за 1 USD
                    <input
                        id="exchange_rate"
                        name="exchange_rate"
                        type="number"
                        min="0.0001"
                        max="99999999.9999"
                        step="0.0001"
                        value="{{ old('exchange_rate', $siteSettings->kgs_per_usd) }}"
                        data-exchange-rate-input
                        @disabled(old('currency', $siteSettings->profitability_currency) !== 'USD')
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                    <span class="mt-1 block text-xs font-normal text-slate-500">Например, при курсе 89 сом за 1 USD обратный пересчёт выполняется делением суммы в сомах на 89.</span>
                </label>
                @error('currency')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('exchange_rate')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить валюту</button>
            </form>
        </section>

        <section id="telegram-notifications" class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки</h1>
                <p class="text-sm text-slate-500">Настройте уведомления и внешний вид приложения.</p>
            </header>
            <h2 class="mb-2 text-lg font-semibold">Уведомления в Telegram</h2>
            @if ($telegramBotConfigured)
                <p class="mb-4 text-sm text-slate-600">
                    Откройте бота и нажмите Start, узнайте свой числовой Chat ID через @userinfobot и укажите его ниже.
                    @if ($telegramBotUsername)
                        Бот: <a class="text-blue-700 hover:underline" href="https://t.me/{{ ltrim($telegramBotUsername, '@') }}" target="_blank" rel="noopener">{{ '@'.ltrim($telegramBotUsername, '@') }}</a>.
                    @else
                        Ссылку на бота уточните у администратора.
                    @endif
                </p>
            @else
                <p class="mb-4 text-sm text-amber-800">Администратору нужно настроить TELEGRAM_BOT_TOKEN в окружении приложения.</p>
            @endif

            <form method="POST" action="{{ route('admin.settings.telegram.update') }}">
                @csrf
                @method('PATCH')
                <label for="telegram_chat_id" class="mb-1 block text-sm font-medium">Telegram Chat ID</label>
                <input
                    id="telegram_chat_id"
                    name="telegram_chat_id"
                    type="text"
                    inputmode="numeric"
                    value="{{ old('telegram_chat_id', auth('admin')->user()->telegram_chat_id) }}"
                    placeholder="Например, 123456789"
                    class="mb-3 w-full rounded border p-2 sm:max-w-sm"
                    aria-describedby="telegram-chat-id-help"
                >
                <p id="telegram-chat-id-help" class="mb-4 text-xs text-slate-500">Оставьте поле пустым, чтобы отключить уведомления.</p>
                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить Chat ID</button>
            </form>
        </section>

        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Часовой пояс</h1>
                <p class="text-sm text-slate-500">Выберите часовой пояс для отображения дат и времени на сайте.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.timezone.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label for="site_timezone" class="block text-sm font-medium">Часовой пояс сайта</label>
                    <select
                        id="site_timezone"
                        name="site_timezone"
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                        @foreach ($timezones as $timezone)
                            <option
                                value="{{ $timezone }}"
                                @selected(old('site_timezone', $siteSettings->site_timezone ?? config('app.timezone')) === $timezone)
                            >{{ $timezone }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Все даты и время на сайте будут отображаться в выбранном часовом поясе.</p>
                </div>

                @error('site_timezone')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить часовой пояс</button>
            </form>
        </section>

        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки импорта</h1>
                <p class="text-sm text-slate-500">Параметры импорта товаров из eBay и уведомления по умолчанию для новых профилей.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.import.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <div>
                    <h2 class="mb-3 text-lg font-semibold">Параметры импорта</h2>

                    <div class="mb-4">
                        <label for="import_max_results" class="block text-sm font-medium">Максимальное количество товаров</label>
                        <input
                            id="import_max_results"
                            name="import_max_results"
                            type="number"
                            min="100"
                            max="100000"
                            step="100"
                            value="{{ old('import_max_results', $siteSettings->import_max_results ?? 10000) }}"
                            class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                        >
                        <p class="mt-1 text-xs text-slate-500">Максимальное количество товаров для импорта за один запуск профиля. По умолчанию: 10 000.</p>
                    </div>

                    <div>
                        <label for="import_page_size" class="block text-sm font-medium">Товаров на страницу</label>
                        <input
                            id="import_page_size"
                            name="import_page_size"
                            type="number"
                            min="10"
                            max="200"
                            step="10"
                            value="{{ old('import_page_size', $siteSettings->import_page_size ?? 200) }}"
                            class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                        >
                        <p class="mt-1 text-xs text-slate-500">Количество товаров, загружаемых за один запрос к eBay API. По умолчанию: 200.</p>
                    </div>
                </div>

                <div>
                    <h2 class="mb-3 text-lg font-semibold">Уведомления по умолчанию</h2>
                    <p class="mb-3 text-sm text-slate-500">Эти настройки применяются при создании нового профиля поиска.</p>

                    <div class="space-y-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="default_notify_new_listings" value="1" class="rounded border"
                                @checked(old('default_notify_new_listings', $siteSettings->default_notify_new_listings ?? true))>
                            <span class="text-sm">Новые товары</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="default_notify_price_drops" value="1" class="rounded border"
                                @checked(old('default_notify_price_drops', $siteSettings->default_notify_price_drops ?? true))>
                            <span class="text-sm">Снижение цен</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="default_notify_search_errors" value="1" class="rounded border"
                                @checked(old('default_notify_search_errors', $siteSettings->default_notify_search_errors ?? true))>
                            <span class="text-sm">Ошибки поиска</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="default_notify_profile_matches" value="1" class="rounded border"
                                @checked(old('default_notify_profile_matches', $siteSettings->default_notify_profile_matches ?? true))>
                            <span class="text-sm">Совпадения профилей</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="default_notify_shared_price_changes" value="1" class="rounded border"
                                @checked(old('default_notify_shared_price_changes', $siteSettings->default_notify_shared_price_changes ?? true))>
                            <span class="text-sm">Изменение цены в нескольких профилях</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="default_notification_frequency" class="block text-sm font-medium">Частота уведомлений</label>
                        <select
                            id="default_notification_frequency"
                            name="default_notification_frequency"
                            class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                        >
                            <option value="immediate" @selected(old('default_notification_frequency', $siteSettings->default_notification_frequency ?? 'immediate') === 'immediate')>Мгновенные</option>
                            <option value="daily" @selected(old('default_notification_frequency', $siteSettings->default_notification_frequency ?? 'immediate') === 'daily')>Ежедневный дайджест</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Как часто отправлять уведомления Telegram по умолчанию.</p>
                    </div>
                </div>

                @error('import_max_results')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('import_page_size')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('default_notification_frequency')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки импорта</button>
            </form>
        </section>

        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Внешний вид</h1>
                <p class="text-sm text-slate-500">Логотип, favicon, подзаголовок, пагинация и тема по умолчанию.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.appearance.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium">Логотип сайта</label>
                    @if ($siteSettings->appearance_logo_path)
                        <div class="mt-2 flex items-center gap-4">
                            <img src="{{ Storage::url($siteSettings->appearance_logo_path) }}" alt="Текущий логотип" class="h-12 w-auto rounded border">
                            <label class="inline-flex cursor-pointer items-center gap-1 rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="file" name="appearance_logo" accept="image/*" class="hidden" onchange="this.form.requestSubmit()">
                                Заменить
                            </label>
                            <button type="submit" name="remove_logo" value="1" class="text-sm text-red-600 hover:text-red-800">Удалить</button>
                        </div>
                    @else
                        <label class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                            Загрузить логотип
                            <input type="file" name="appearance_logo" accept="image/*" class="hidden">
                        </label>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">Изображение логотипа (PNG, JPG, SVG). Максимум 2 МБ. Рекомендуемый размер: 200×60 px.</p>
                </div>

                <!-- Favicon -->
                <div>
                    <label class="block text-sm font-medium">Favicon</label>
                    @if ($siteSettings->appearance_favicon_path)
                        <div class="mt-2 flex items-center gap-4">
                            <img src="{{ Storage::url($siteSettings->appearance_favicon_path) }}" alt="Текущий favicon" class="h-8 w-8 rounded border">
                            <label class="inline-flex cursor-pointer items-center gap-1 rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="file" name="appearance_favicon" accept="image/png,image/x-icon" class="hidden">
                                Заменить
                            </label>
                            <button type="submit" name="remove_favicon" value="1" class="text-sm text-red-600 hover:text-red-800">Удалить</button>
                        </div>
                    @else
                        <label class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                            Загрузить favicon
                            <input type="file" name="appearance_favicon" accept="image/png,image/x-icon" class="hidden">
                        </label>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">Иконка сайта (ICO или PNG). Максимум 512 КБ. Рекомендуемый размер: 64×64 px.</p>
                </div>

                <!-- Site Subtitle -->
                <div>
                    <label for="appearance_site_subtitle" class="block text-sm font-medium">Подзаголовок в шапке</label>
                    <input
                        id="appearance_site_subtitle"
                        name="appearance_site_subtitle"
                        type="text"
                        maxlength="255"
                        value="{{ old('appearance_site_subtitle', $siteSettings->appearance_site_subtitle ?? 'Поиск товаров на eBay') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">Подзаголовок, отображаемый под названием сайта в шапке.</p>
                </div>

                <!-- Default Per Page -->
                <div>
                    <label for="appearance_default_per_page" class="block text-sm font-medium">Товаров на странице (по умолчанию)</label>
                    <select
                        id="appearance_default_per_page"
                        name="appearance_default_per_page"
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                        @foreach ([10, 25, 50, 100, 200] as $count)
                            <option value="{{ $count }}" @selected(old('appearance_default_per_page', $siteSettings->appearance_default_per_page ?? 25) == $count)>
                                {{ $count }} товаров
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Количество товаров на странице по умолчанию для новых пользователей.</p>
                </div>

                <!-- Default Theme -->
                <div>
                    <label for="appearance_default_theme" class="block text-sm font-medium">Тема по умолчанию</label>
                    <select
                        id="appearance_default_theme"
                        name="appearance_default_theme"
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                        <option value="light" @selected(old('appearance_default_theme', $siteSettings->appearance_default_theme ?? 'light') === 'light')>Светлая тема</option>
                        <option value="dark" @selected(old('appearance_default_theme', $siteSettings->appearance_default_theme ?? 'light') === 'dark')>Тёмная тема</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Тема оформления для новых пользователей (без выбранной темы в браузере).</p>
                </div>

                @error('appearance_logo')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('appearance_favicon')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('appearance_site_subtitle')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('appearance_default_per_page')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('appearance_default_theme')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить внешний вид</button>
            </form>
        </section>

        <!-- Email SMTP Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">SMTP и отправитель</h1>
                <p class="text-sm text-slate-500">Настройки почтового сервера для отправки email-уведомлений.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.email-smtp.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- SMTP Host -->
                <div>
                    <label for="email_smtp_host" class="block text-sm font-medium">SMTP-сервер</label>
                    <input
                        id="email_smtp_host"
                        name="email_smtp_host"
                        type="text"
                        maxlength="255"
                        value="{{ old('email_smtp_host', $siteSettings->email_smtp_host ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                    >
                    <p class="mt-1 text-xs text-slate-500">Адрес SMTP-сервера (например, smtp.gmail.com).</p>
                </div>

                <!-- SMTP Port -->
                <div>
                    <label for="email_smtp_port" class="block text-sm font-medium">Порт</label>
                    <input
                        id="email_smtp_port"
                        name="email_smtp_port"
                        type="number"
                        min="1"
                        max="65535"
                        value="{{ old('email_smtp_port', $siteSettings->email_smtp_port ?? 587) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Порт SMTP-сервера (587 для TLS, 465 для SSL).</p>
                </div>

                <!-- Encryption -->
                <div>
                    <label for="email_smtp_encryption" class="block text-sm font-medium">Шифрование</label>
                    <select
                        id="email_smtp_encryption"
                        name="email_smtp_encryption"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="tls" @selected(old('email_smtp_encryption', $siteSettings->email_smtp_encryption ?? 'tls') === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('email_smtp_encryption', $siteSettings->email_smtp_encryption ?? 'tls') === 'ssl')>SSL</option>
                        <option value="" @selected(old('email_smtp_encryption', $siteSettings->email_smtp_encryption ?? 'tls') === '')>Без шифрования</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Протокол шифрования подключения к SMTP-серверу.</p>
                </div>

                <!-- SMTP Username -->
                <div>
                    <label for="email_smtp_username" class="block text-sm font-medium">Логин</label>
                    <input
                        id="email_smtp_username"
                        name="email_smtp_username"
                        type="text"
                        maxlength="255"
                        value="{{ old('email_smtp_username', $siteSettings->email_smtp_username ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                    >
                    <p class="mt-1 text-xs text-slate-500">Логин для аутентификации на SMTP-сервере (обычно email-адрес).</p>
                </div>

                <!-- SMTP Password -->
                <div>
                    <label for="email_smtp_password" class="block text-sm font-medium">Пароль</label>
                    <input
                        id="email_smtp_password"
                        name="email_smtp_password"
                        type="password"
                        maxlength="255"
                        value="{{ old('email_smtp_password', $siteSettings->email_smtp_password ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                    >
                    <p class="mt-1 text-xs text-slate-500">Пароль для аутентификации на SMTP-сервере. Оставьте пустым, если не меняете.</p>
                </div>

                <hr class="border-slate-200">

                <!-- From Address -->
                <div>
                    <label for="email_from_address" class="block text-sm font-medium">Email отправителя</label>
                    <input
                        id="email_from_address"
                        name="email_from_address"
                        type="email"
                        maxlength="255"
                        value="{{ old('email_from_address', $siteSettings->email_from_address ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                    >
                    <p class="mt-1 text-xs text-slate-500">Email-адрес, от которого отправляются уведомления.</p>
                </div>

                <!-- From Name -->
                <div>
                    <label for="email_from_name" class="block text-sm font-medium">Имя отправителя</label>
                    <input
                        id="email_from_name"
                        name="email_from_name"
                        type="text"
                        maxlength="255"
                        value="{{ old('email_from_name', $siteSettings->email_from_name ?? 'Finder') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">Имя отправителя, отображаемое в письмах.</p>
                </div>

                @error('email_smtp_host')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_smtp_port')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_smtp_encryption')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_smtp_username')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_smtp_password')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_from_address')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_from_name')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки email</button>
            </form>
        </section>

        <!-- System Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Системные настройки</h1>
                <p class="text-sm text-slate-500">Режим обслуживания, очистка кэша, логирование и отладка.</p>
            </header>

            <!-- Maintenance Mode -->
            <div class="mb-6 rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">Режим обслуживания</h2>
                        <p class="text-sm text-slate-500">При включении сайт становится недоступен для пользователей.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.maintenance.toggle') }}">
                        @csrf
                        @if ($isDown)
                            <button type="submit" name="enable" value="0" class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Выключить</button>
                        @else
                            <button type="submit" name="enable" value="1" class="rounded bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Включить</button>
                        @endif
                    </form>
                </div>
                @if ($isDown)
                    <p class="mt-2 text-sm text-amber-600">⚠️ Режим обслуживания включён</p>
                @endif
            </div>

            <!-- Clear Cache -->
            <div class="mb-6 rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">Очистка кэша</h2>
                        <p class="text-sm text-slate-500">Очистить кэш конфигурации, маршрутов и представлений.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.clear-cache') }}">
                        @csrf
                        <button type="submit" class="rounded bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Очистить кэш</button>
                    </form>
                </div>
            </div>

            <hr class="border-slate-200">

            <!-- System Settings Form -->
            <form method="POST" action="{{ route('admin.settings.system.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PATCH')

                <!-- Log Level -->
                <div>
                    <label for="system_log_level" class="block text-sm font-medium">Уровень логирования</label>
                    <select
                        id="system_log_level"
                        name="system_log_level"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="debug" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'debug')>Debug (все)</option>
                        <option value="info" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'info')>Info</option>
                        <option value="notice" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'notice')>Notice</option>
                        <option value="warning" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'warning')>Warning (предупреждения)</option>
                        <option value="error" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'error')>Error (ошибки)</option>
                        <option value="critical" @selected(old('system_log_level', $siteSettings->system_log_level ?? 'warning') === 'critical')>Critical (критические)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Минимальный уровень событий, записываемых в лог.</p>
                </div>

                <!-- Debug Mode -->
                <div class="flex items-center gap-3">
                    <input
                        id="system_debug_mode"
                        name="system_debug_mode"
                        type="checkbox"
                        value="1"
                        @checked(old('system_debug_mode', $siteSettings->system_debug_mode ?? false) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="system_debug_mode" class="text-sm font-medium">Режим отладки</label>
                </div>
                <p class="text-xs text-slate-500">Показывать подробные ошибки на сайте. Не включайте на продакшене.</p>

                <!-- Max Execution Time -->
                <div>
                    <label for="system_max_execution_time" class="block text-sm font-medium">Макс. время выполнения (сек)</label>
                    <input
                        id="system_max_execution_time"
                        name="system_max_execution_time"
                        type="number"
                        min="30"
                        max="3600"
                        value="{{ old('system_max_execution_time', $siteSettings->system_max_execution_time ?? 300) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Максимальное время выполнения скрипта в секундах (30–3600).</p>
                </div>

                @error('system_log_level')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('system_max_execution_time')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить системные настройки</button>
            </form>
        </section>

        <!-- Search and Filters Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки поиска и фильтров</h1>
                <p class="text-sm text-slate-500">Сортировка по умолчанию, поле поиска, глобальные фильтры по цене и состоянию.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.search-filters.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Default Sort -->
                <div>
                    <label for="search_default_sort" class="block text-sm font-medium">Сортировка по умолчанию</label>
                    <select
                        id="search_default_sort"
                        name="search_default_sort"
                        class="mt-1 w-full rounded border p-2 sm:max-w-md"
                    >
                        <option value="price_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'price_asc')>Цена (по возрастанию)</option>
                        <option value="price_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'price_desc')>Цена (по убыванию)</option>
                        <option value="title_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'title_asc')>Название (А–Я)</option>
                        <option value="title_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'title_desc')>Название (Я–А)</option>
                        <option value="date_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'date_desc')>Дата (новые сначала)</option>
                        <option value="date_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'date_asc')>Дата (старые сначала)</option>
                        <option value="brand_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'brand_asc')>Бренд (А–Я)</option>
                        <option value="brand_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'brand_desc')>Бренд (Я–А)</option>
                        <option value="condition_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'condition_asc')>Состояние (А–Я)</option>
                        <option value="condition_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'condition_desc')>Состояние (Я–А)</option>
                        <option value="seller_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'seller_asc')>Продавец (А–Я)</option>
                        <option value="seller_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'seller_desc')>Продавец (Я–А)</option>
                        <option value="value_asc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'value_asc')>Оценка (по возрастанию)</option>
                        <option value="value_desc" @selected(old('search_default_sort', $siteSettings->search_default_sort ?? 'price_asc') === 'value_desc')>Оценка (по убыванию)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Как товары отсортированы при первом открытии списка товаров.</p>
                </div>

                <!-- Default Search Field -->
                <div>
                    <label for="search_default_field" class="block text-sm font-medium">Поиск в поле</label>
                    <select
                        id="search_default_field"
                        name="search_default_field"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="title" @selected(old('search_default_field', $siteSettings->search_default_field ?? 'title') === 'title')>Название товара</option>
                        <option value="seller_description" @selected(old('search_default_field', $siteSettings->search_default_field ?? 'title') === 'seller_description')>Описание продавца</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">В каком поле искать текст по умолчанию.</p>
                </div>

                <!-- Default Status -->
                <div>
                    <label for="search_default_status" class="block text-sm font-medium">Статус по умолчанию</label>
                    <select
                        id="search_default_status"
                        name="search_default_status"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="" @selected(!old('search_default_status', $siteSettings->search_default_status ?? ''))>Все товары</option>
                        <option value="new" @selected(old('search_default_status', $siteSettings->search_default_status ?? '') === 'new')>Новые</option>
                        <option value="watching" @selected(old('search_default_status', $siteSettings->search_default_status ?? '') === 'watching')>В избранном</option>
                        <option value="buy" @selected(old('search_default_status', $siteSettings->search_default_status ?? '') === 'buy')>Купленные</option>
                        <option value="skipped" @selected(old('search_default_status', $siteSettings->search_default_status ?? '') === 'skipped')>Пропущенные</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Какие товары показывать при первом открытии списка.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Price Min -->
                <div>
                    <label for="search_price_min" class="block text-sm font-medium">Минимальная цена (USD)</label>
                    <input
                        id="search_price_min"
                        name="search_price_min"
                        type="number"
                        min="0"
                        max="999999"
                        value="{{ old('search_price_min', $siteSettings->search_price_min ?? 0) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Глобальный фильтр: не показывать товары дешевле этой суммы. 0 — без ограничения.</p>
                </div>

                <!-- Price Max -->
                <div>
                    <label for="search_price_max" class="block text-sm font-medium">Максимальная цена (USD)</label>
                    <input
                        id="search_price_max"
                        name="search_price_max"
                        type="number"
                        min="0"
                        max="999999"
                        value="{{ old('search_price_max', $siteSettings->search_price_max ?? 0) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Глобальный фильтр: не показывать товары дороже этой суммы. 0 — без ограничения.</p>
                </div>

                <!-- Default Condition -->
                <div>
                    <label for="search_default_condition" class="block text-sm font-medium">Состояние по умолчанию</label>
                    <input
                        id="search_default_condition"
                        name="search_default_condition"
                        type="text"
                        maxlength="50"
                        value="{{ old('search_default_condition', $siteSettings->search_default_condition ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Фильтр по состоянию товара по умолчанию (например, New, Refurbished, Used). Оставьте пустым для всех состояний.</p>
                </div>

                @error('search_default_sort')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('search_default_field')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('search_default_status')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('search_price_min')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('search_price_max')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('search_default_condition')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки поиска</button>
            </form>
        </section>

        <!-- Catalog Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки каталога товаров</h1>
                <p class="text-sm text-slate-500">Валюта отображения, формат цен и видимые столбцы в каталоге.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.catalog.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Currency -->
                <div>
                    <label for="catalog_currency" class="block text-sm font-medium">Валюта отображения цен</label>
                    <select
                        id="catalog_currency"
                        name="catalog_currency"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="USD" @selected(old('catalog_currency', $siteSettings->catalog_currency ?? 'USD') === 'USD')>USD (доллары США)</option>
                        <option value="KGS" @selected(old('catalog_currency', $siteSettings->catalog_currency ?? 'USD') === 'KGS')>KGS (сомы КР)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Валюта, в которой отображаются цены на сайте. При выборе KGS цены конвертируются по курсу из настроек.</p>
                </div>

                <!-- Price Format -->
                <div>
                    <label for="catalog_price_format" class="block text-sm font-medium">Формат цены</label>
                    <select
                        id="catalog_price_format"
                        name="catalog_price_format"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="before" @selected(old('catalog_price_format', $siteSettings->catalog_price_format ?? 'before') === 'before')>Символ перед ценой ($199.00)</option>
                        <option value="after" @selected(old('catalog_price_format', $siteSettings->catalog_price_format ?? 'before') === 'after')>Символ после цены (199.00 USD)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Как отображается символ валюты относительно числа.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Show Shipping -->
                <div class="flex items-center gap-3">
                    <input
                        id="catalog_show_shipping"
                        name="catalog_show_shipping"
                        type="checkbox"
                        value="1"
                        @checked(old('catalog_show_shipping', $siteSettings->catalog_show_shipping ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="catalog_show_shipping" class="text-sm font-medium">Показывать доставку отдельно</label>
                </div>
                <p class="text-xs text-slate-500">Отображать стоимость доставки под ценой товара в списке.</p>

                <!-- Show Image -->
                <div class="flex items-center gap-3">
                    <input
                        id="catalog_show_image"
                        name="catalog_show_image"
                        type="checkbox"
                        value="1"
                        @checked(old('catalog_show_image', $siteSettings->catalog_show_image ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="catalog_show_image" class="text-sm font-medium">Показывать изображение товара</label>
                </div>
                <p class="text-xs text-slate-500">Отображать миниатюры изображений в списке товаров.</p>

                <!-- Show Seller -->
                <div class="flex items-center gap-3">
                    <input
                        id="catalog_show_seller"
                        name="catalog_show_seller"
                        type="checkbox"
                        value="1"
                        @checked(old('catalog_show_seller', $siteSettings->catalog_show_seller ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="catalog_show_seller" class="text-sm font-medium">Показывать продавца</label>
                </div>
                <p class="text-xs text-slate-500">Отображать столбец с названием продавца в списке товаров.</p>

                <!-- Show Condition -->
                <div class="flex items-center gap-3">
                    <input
                        id="catalog_show_condition"
                        name="catalog_show_condition"
                        type="checkbox"
                        value="1"
                        @checked(old('catalog_show_condition', $siteSettings->catalog_show_condition ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="catalog_show_condition" class="text-sm font-medium">Показывать состояние товара</label>
                </div>
                <p class="text-xs text-slate-500">Отображать столбец с состоянием товара (New, Used, Refurbished).</p>

                @error('catalog_currency')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('catalog_price_format')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки каталога</button>
            </form>
        </section>

        <!-- User Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки пользователей</h1>
                <p class="text-sm text-slate-500">Регистрация, верификация, сессии и безопасность.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.users.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Registration Enabled -->
                <div class="flex items-center gap-3">
                    <input
                        id="user_registration_enabled"
                        name="user_registration_enabled"
                        type="checkbox"
                        value="1"
                        @checked(old('user_registration_enabled', $siteSettings->user_registration_enabled ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="user_registration_enabled" class="text-sm font-medium">Разрешить регистрацию</label>
                </div>
                <p class="text-xs text-slate-500">При отключении новые пользователи не смогут зарегистрироваться на сайте.</p>

                <!-- Email Verification -->
                <div class="flex items-center gap-3">
                    <input
                        id="user_email_verification"
                        name="user_email_verification"
                        type="checkbox"
                        value="1"
                        @checked(old('user_email_verification', $siteSettings->user_email_verification ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="user_email_verification" class="text-sm font-medium">Требовать верификацию email</label>
                </div>
                <p class="text-xs text-slate-500">При регистрации пользователь должен подтвердить email перед входом.</p>

                <hr class="border-slate-200">

                <!-- Session Lifetime -->
                <div>
                    <label for="user_session_lifetime" class="block text-sm font-medium">Срок сессии (минуты)</label>
                    <select
                        id="user_session_lifetime"
                        name="user_session_lifetime"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="30" @selected(old('user_session_lifetime', $siteSettings->user_session_lifetime ?? 43200) == 30)>30 минут</option>
                        <option value="60" @selected(old('user_session_lifetime', $siteSettings->user_session_lifetime ?? 43200) == 60)>1 час</option>
                        <option value="1440" @selected(old('user_session_lifetime', $siteSettings->user_session_lifetime ?? 43200) == 1440)>24 часа</option>
                        <option value="43200" @selected(old('user_session_lifetime', $siteSettings->user_session_lifetime ?? 43200) == 43200)>30 дней</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Через сколько времени пользователь будет вынужден войти заново.</p>
                </div>

                <!-- Login Max Attempts -->
                <div>
                    <label for="user_login_max_attempts" class="block text-sm font-medium">Макс. попыток входа</label>
                    <input
                        id="user_login_max_attempts"
                        name="user_login_max_attempts"
                        type="number"
                        min="1"
                        max="20"
                        value="{{ old('user_login_max_attempts', $siteSettings->user_login_max_attempts ?? 5) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Количество неудачных попыток входа перед временной блокировкой аккаунта.</p>
                </div>

                <!-- Password Min Length -->
                <div>
                    <label for="user_password_min_length" class="block text-sm font-medium">Мин. длина пароля</label>
                    <input
                        id="user_password_min_length"
                        name="user_password_min_length"
                        type="number"
                        min="4"
                        max="32"
                        value="{{ old('user_password_min_length', $siteSettings->user_password_min_length ?? 8) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Минимальное количество символов в пароле при регистрации.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Welcome Email -->
                <div class="flex items-center gap-3">
                    <input
                        id="user_welcome_email"
                        name="user_welcome_email"
                        type="checkbox"
                        value="1"
                        @checked(old('user_welcome_email', $siteSettings->user_welcome_email ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="user_welcome_email" class="text-sm font-medium">Приветственное письмо</label>
                </div>
                <p class="text-xs text-slate-500">Отправлять email-письмо новым пользователям после регистрации.</p>

                @error('user_session_lifetime')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('user_login_max_attempts')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('user_password_min_length')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки пользователей</button>
            </form>
        </section>

        <!-- Notifications and Reports Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Настройки уведомлений и отчётов</h1>
                <p class="text-sm text-slate-500">Логирование событий, email-отчёты, Telegram-сводки и алерты.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.notifications.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <h2 class="text-sm font-semibold text-slate-700">Логирование событий</h2>

                <!-- Log Import -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_log_import"
                        name="notify_log_import"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_log_import', $siteSettings->notify_log_import ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_log_import" class="text-sm font-medium">Импорт товаров</label>
                </div>
                <p class="text-xs text-slate-500">Записывать в лог события импорта (старт, завершение, количество товаров).</p>

                <!-- Log Errors -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_log_errors"
                        name="notify_log_errors"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_log_errors', $siteSettings->notify_log_errors ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_log_errors" class="text-sm font-medium">Ошибки</label>
                </div>
                <p class="text-xs text-slate-500">Записывать в лог все ошибки парсинга и импорта.</p>

                <!-- Log Price Changes -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_log_price_changes"
                        name="notify_log_price_changes"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_log_price_changes', $siteSettings->notify_log_price_changes ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_log_price_changes" class="text-sm font-medium">Изменения цен</label>
                </div>
                <p class="text-xs text-slate-500">Записывать в лог изменения цен товаров.</p>

                <!-- Log New Items -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_log_new_items"
                        name="notify_log_new_items"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_log_new_items', $siteSettings->notify_log_new_items ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_log_new_items" class="text-sm font-medium">Новые товары</label>
                </div>
                <p class="text-xs text-slate-500">Записывать в лог появление новых товаров при импорте.</p>

                <hr class="border-slate-200">

                <h2 class="text-sm font-semibold text-slate-700">Отчёты</h2>

                <!-- Email Reports -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_email_reports"
                        name="notify_email_reports"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_email_reports', $siteSettings->notify_email_reports ?? false) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_email_reports" class="text-sm font-medium">Email-отчёты</label>
                </div>
                <p class="text-xs text-slate-500">Отправлять отчёты с итогами импорта на email администратора.</p>

                <!-- Email Schedule -->
                <div>
                    <label for="notify_email_schedule" class="block text-sm font-medium">Расписание email-отчётов</label>
                    <select
                        id="notify_email_schedule"
                        name="notify_email_schedule"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="daily" @selected(old('notify_email_schedule', $siteSettings->notify_email_schedule ?? 'daily') === 'daily')>Ежедневно</option>
                        <option value="weekly" @selected(old('notify_email_schedule', $siteSettings->notify_email_schedule ?? 'daily') === 'weekly')>Еженедельно</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Как часто отправлять email-отчёты.</p>
                </div>

                <!-- Telegram Digest -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_telegram_digest"
                        name="notify_telegram_digest"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_telegram_digest', $siteSettings->notify_telegram_digest ?? false) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_telegram_digest" class="text-sm font-medium">Telegram-сводка</label>
                </div>
                <p class="text-xs text-slate-500">Отправлять ежедневную сводку по импорту в Telegram.</p>

                <hr class="border-slate-200">

                <h2 class="text-sm font-semibold text-slate-700">Мгновенные уведомления</h2>

                <!-- Error Alerts -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_error_alerts"
                        name="notify_error_alerts"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_error_alerts', $siteSettings->notify_error_alerts ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_error_alerts" class="text-sm font-medium">Уведомления об ошибках</label>
                </div>
                <p class="text-xs text-slate-500">Мгновенно уведомлять в Telegram при ошибке импорта или парсинга.</p>

                <!-- Price Drop Alerts -->
                <div class="flex items-center gap-3">
                    <input
                        id="notify_price_drop_alerts"
                        name="notify_price_drop_alerts"
                        type="checkbox"
                        value="1"
                        @checked(old('notify_price_drop_alerts', $siteSettings->notify_price_drop_alerts ?? true) === true)
                        class="h-4 w-4 rounded border-slate-300 text-blue-700"
                    >
                    <label for="notify_price_drop_alerts" class="text-sm font-medium">Уведомления о снижении цен</label>
                </div>
                <p class="text-xs text-slate-500">Мгновенно уведомлять в Telegram при снижении цены ниже целевой.</p>

                @error('notify_email_schedule')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки уведомлений</button>
            </form>
        </section>

        <!-- Footer Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Футер и юридические страницы</h1>
                <p class="text-sm text-slate-500">Копирайт, контакты, ссылки на соцсети и содержание юридических страниц.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.footer.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Copyright -->
                <div>
                    <label for="footer_copyright" class="block text-sm font-medium">Копирайт</label>
                    <input
                        id="footer_copyright"
                        name="footer_copyright"
                        type="text"
                        maxlength="255"
                        value="{{ old('footer_copyright', $siteSettings->footer_copyright ?? '') }}"
                        class="mt-1 w-full rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">Текст копирайта в нижней части сайта (например, «© 2026 Finder. Все права защищены.»).</p>
                </div>

                <!-- Contact Email -->
                <div>
                    <label for="footer_contact_email" class="block text-sm font-medium">Контактный email</label>
                    <input
                        id="footer_contact_email"
                        name="footer_contact_email"
                        type="email"
                        maxlength="255"
                        value="{{ old('footer_contact_email', $siteSettings->footer_contact_email ?? '') }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-lg"
                    >
                    <p class="mt-1 text-xs text-slate-500">Email для связи, отображаемый в футере сайта.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Social Links -->
                <h2 class="text-sm font-semibold text-slate-700">Ссылки на соцсети</h2>

                <!-- Telegram -->
                <div>
                    <label for="footer_social_telegram" class="block text-sm font-medium">Telegram</label>
                    <input
                        id="footer_social_telegram"
                        name="footer_social_telegram"
                        type="url"
                        maxlength="255"
                        value="{{ old('footer_social_telegram', $siteSettings->footer_social_telegram ?? '') }}"
                        class="mt-1 w-full rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">URL канала или группы в Telegram (например, https://t.me/your_channel).</p>
                </div>

                <!-- Facebook -->
                <div>
                    <label for="footer_social_facebook" class="block text-sm font-medium">Facebook</label>
                    <input
                        id="footer_social_facebook"
                        name="footer_social_facebook"
                        type="url"
                        maxlength="255"
                        value="{{ old('footer_social_facebook', $siteSettings->footer_social_facebook ?? '') }}"
                        class="mt-1 w-full rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">URL страницы в Facebook.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Privacy Policy -->
                <h2 class="text-sm font-semibold text-slate-700">Юридические страницы</h2>

                <div>
                    <label for="footer_privacy_policy" class="block text-sm font-medium">Политика конфиденциальности</label>
                    <textarea
                        id="footer_privacy_policy"
                        name="footer_privacy_policy"
                        rows="6"
                        class="mt-1 w-full rounded border p-2"
                    >{{ old('footer_privacy_policy', $siteSettings->footer_privacy_policy ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Содержание страницы политики конфиденциальности (HTML-разметка поддерживается). Оставьте пустым, чтобы скрыть ссылку.</p>
                </div>

                <!-- Terms of Service -->
                <div>
                    <label for="footer_terms_of_service" class="block text-sm font-medium">Условия использования</label>
                    <textarea
                        id="footer_terms_of_service"
                        name="footer_terms_of_service"
                        rows="6"
                        class="mt-1 w-full rounded border p-2"
                    >{{ old('footer_terms_of_service', $siteSettings->footer_terms_of_service ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Содержание страницы условий использования (HTML-разметка поддерживается). Оставьте пустым, чтобы скрыть ссылку.</p>
                </div>

                @error('footer_copyright')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('footer_contact_email')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('footer_social_telegram')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('footer_social_facebook')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки футера</button>
            </form>
        </section>

        <!-- Caching and Performance Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Кэширование и производительность</h1>
                <p class="text-sm text-slate-500">Кэширование страниц, кэширование eBay API, сжатие и lazy loading.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.caching.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Page Caching -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="cache_pages_enabled"
                            name="cache_pages_enabled"
                            type="checkbox"
                            value="1"
                            @checked(old('cache_pages_enabled', $siteSettings->cache_pages_enabled ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Кэширование страниц</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Кэшировать HTML-страницы публичных страниц сайта для ускорения загрузки.</p>
                </div>

                <!-- Page Cache TTL -->
                <div>
                    <label for="cache_pages_ttl" class="block text-sm font-medium">Время жизни кэша страниц (минуты)</label>
                    <input
                        id="cache_pages_ttl"
                        name="cache_pages_ttl"
                        type="number"
                        min="1"
                        max="1440"
                        value="{{ old('cache_pages_ttl', $siteSettings->cache_pages_ttl ?? 60) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Сколько минут хранить кэш страниц. По умолчанию: 60.</p>
                </div>

                <hr class="border-slate-200">

                <!-- eBay API Caching -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="cache_ebay_api_enabled"
                            name="cache_ebay_api_enabled"
                            type="checkbox"
                            value="1"
                            @checked(old('cache_ebay_api_enabled', $siteSettings->cache_ebay_api_enabled ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Кэширование запросов к eBay API</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Кэшировать результаты запросов к eBay API для уменьшения количества запросов и снижения нагрузки.</p>
                </div>

                <!-- eBay API Cache TTL -->
                <div>
                    <label for="cache_ebay_api_ttl" class="block text-sm font-medium">Время жизни кэша eBay API (минуты)</label>
                    <input
                        id="cache_ebay_api_ttl"
                        name="cache_ebay_api_ttl"
                        type="number"
                        min="1"
                        max="1440"
                        value="{{ old('cache_ebay_api_ttl', $siteSettings->cache_ebay_api_ttl ?? 5) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Сколько минут хранить кэш результатов eBay API. По умолчанию: 5.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Gzip Compression -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="performance_gzip"
                            name="performance_gzip"
                            type="checkbox"
                            value="1"
                            @checked(old('performance_gzip', $siteSettings->performance_gzip ?? true))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Gzip-сжатие ответов</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Сжимать ответы сервера перед отправкой браузеру для уменьшения объёма передаваемых данных.</p>
                </div>

                <!-- Lazy Loading -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="performance_lazy_loading"
                            name="performance_lazy_loading"
                            type="checkbox"
                            value="1"
                            @checked(old('performance_lazy_loading', $siteSettings->performance_lazy_loading ?? true))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Отложенная загрузка изображений</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Загружать изображения товаров только при прокрутке до них для ускорения первоначальной загрузки страницы.</p>
                </div>

                @error('cache_pages_ttl')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('cache_ebay_api_ttl')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки кэширования</button>
            </form>
        </section>

        <!-- Email Notifications Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Email-уведомления</h1>
                <p class="text-sm text-slate-500">Типы email-уведомлений, получатели и шаблоны тем писем.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.email-notifications.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Notification Types -->
                <h2 class="text-sm font-semibold text-slate-700">Типы уведомлений</h2>

                <!-- New Items -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="email_notify_new_items"
                            name="email_notify_new_items"
                            type="checkbox"
                            value="1"
                            @checked(old('email_notify_new_items', $siteSettings->email_notify_new_items ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Новые товары</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Отправлять email при нахождении новых товаров по профилям поиска.</p>
                </div>

                <!-- Price Drops -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="email_notify_price_drops"
                            name="email_notify_price_drops"
                            type="checkbox"
                            value="1"
                            @checked(old('email_notify_price_drops', $siteSettings->email_notify_price_drops ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Снижение цен</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Отправлять email при снижении цены товара ниже целевой.</p>
                </div>

                <!-- Errors -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="email_notify_errors"
                            name="email_notify_errors"
                            type="checkbox"
                            value="1"
                            @checked(old('email_notify_errors', $siteSettings->email_notify_errors ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Ошибки импорта</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Отправлять email при ошибках парсинга профилей.</p>
                </div>

                <!-- Matches -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="email_notify_matches"
                            name="email_notify_matches"
                            type="checkbox"
                            value="1"
                            @checked(old('email_notify_matches', $siteSettings->email_notify_matches ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Совпадения профилей</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Отправлять email при совпадении товара с критериями профиля.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Recipients -->
                <div>
                    <label for="email_recipients" class="block text-sm font-medium">Получатели email-уведомлений</label>
                    <textarea
                        id="email_recipients"
                        name="email_recipients"
                        rows="2"
                        class="mt-1 w-full rounded border p-2 font-mono text-sm"
                    >{{ old('email_recipients', $siteSettings->email_recipients ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Email-адреса получателей через запятую (например, admin@example.com, user@example.com). Оставьте пустым, чтобы не отправлять.</p>
                </div>

                <!-- Subject Template -->
                <div>
                    <label for="email_subject_template" class="block text-sm font-medium">Шаблон темы письма</label>
                    <input
                        id="email_subject_template"
                        name="email_subject_template"
                        type="text"
                        maxlength="255"
                        value="{{ old('email_subject_template', $siteSettings->email_subject_template ?? '') }}"
                        class="mt-1 w-full rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">Шаблон темы с переменными: {site_name}, {profile}, {count}. Например: «{site_name}: {count} новых товаров по профилю {profile}». Оставьте пустым для темы по умолчанию.</p>
                </div>

                @error('email_recipients')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('email_subject_template')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки email</button>
            </form>
        </section>

        <!-- Currencies Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Валюты и конвертация</h1>
                <p class="text-sm text-slate-500">Курсы валют, автообновление и отображение цен в нескольких валютах.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.currencies.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- EUR/USD -->
                <div>
                    <label for="eur_per_usd" class="block text-sm font-medium">Курс EUR/USD</label>
                    <input
                        id="eur_per_usd"
                        name="eur_per_usd"
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        value="{{ old('eur_per_usd', $siteSettings->eur_per_usd ?? 1.0) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Сколько USD стоит 1 EUR. По умолчанию: 1.0.</p>
                </div>

                <!-- GBP/USD -->
                <div>
                    <label for="gbp_per_usd" class="block text-sm font-medium">Курс GBP/USD</label>
                    <input
                        id="gbp_per_usd"
                        name="gbp_per_usd"
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        value="{{ old('gbp_per_usd', $siteSettings->gbp_per_usd ?? 1.0) }}"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-slate-500">Сколько USD стоит 1 GBP. По умолчанию: 1.0.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Auto Update -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="currency_auto_update"
                            name="currency_auto_update"
                            type="checkbox"
                            value="1"
                            @checked(old('currency_auto_update', $siteSettings->currency_auto_update ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Автоматическое обновление курсов</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Автоматически обновлять курсы валют по расписанию.</p>
                </div>

                <!-- Update Schedule -->
                <div>
                    <label for="currency_update_schedule" class="block text-sm font-medium">Расписание обновления</label>
                    <select
                        id="currency_update_schedule"
                        name="currency_update_schedule"
                        class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                    >
                        <option value="daily" @selected(old('currency_update_schedule', $siteSettings->currency_update_schedule ?? 'daily') === 'daily')>Ежедневно</option>
                        <option value="weekly" @selected(old('currency_update_schedule', $siteSettings->currency_update_schedule ?? 'daily') === 'weekly')>Еженедельно</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Как часто обновлять курсы валют.</p>
                </div>

                <!-- Show Multi Currency -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="currency_show_multi"
                            name="currency_show_multi"
                            type="checkbox"
                            value="1"
                            @checked(old('currency_show_multi', $siteSettings->currency_show_multi ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Отображать цены в нескольких валютах</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Показывать цену в USD и KGS одновременно на карточке товара.</p>
                </div>

                @error('eur_per_usd')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('gbp_per_usd')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                @error('currency_update_schedule')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки валют</button>
            </form>
        </section>

        <!-- API & Webhooks Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">API и вебхуки</h1>
                <p class="text-sm text-slate-500">API-ключ для внешних систем и вебхуки для уведомлений при событиях.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.api-and-webhooks.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Enable API -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            id="api_enabled"
                            name="api_enabled"
                            type="checkbox"
                            value="1"
                            @checked(old('api_enabled', $siteSettings->api_enabled ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Включить API для внешних систем</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Разрешить доступ к данным сайта через API с использованием API-ключа.</p>
                </div>

                <!-- API Key -->
                <div>
                    <label for="api_key" class="block text-sm font-medium">API-ключ</label>
                    <div class="flex gap-2">
                        <input
                            id="api_key"
                            type="text"
                            readonly
                            value="{{ old('api_key', $siteSettings->api_key ?? '') }}"
                            class="mt-1 flex-1 rounded border bg-slate-50 p-2 font-mono text-sm"
                        >
                        <button type="submit" name="regenerate_api_key" value="1" class="mt-1 rounded bg-slate-200 px-3 py-2 text-sm font-medium hover:bg-slate-300 whitespace-nowrap">Обновить</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Используйте этот ключ для аутентификации запросов к API. Нажмите «Обновить» для генерации нового ключа.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Webhook URL -->
                <div>
                    <label for="webhook_url" class="block text-sm font-medium">Webhook URL</label>
                    <input
                        id="webhook_url"
                        name="webhook_url"
                        type="url"
                        value="{{ old('webhook_url', $siteSettings->webhook_url ?? '') }}"
                        placeholder="https://example.com/webhook"
                        class="mt-1 w-full rounded border p-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">Внешний URL для отправки уведомлений при событиях на сайте.</p>
                </div>

                <!-- Webhook Events -->
                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input
                            id="webhook_new_items"
                            name="webhook_new_items"
                            type="checkbox"
                            value="1"
                            @checked(old('webhook_new_items', $siteSettings->webhook_new_items ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Новые товары</span>
                    </label>
                    <p class="ml-6 text-xs text-slate-500">Отправлять вебхук при нахождении новых товаров по профилям.</p>

                    <label class="flex items-center gap-2">
                        <input
                            id="webhook_price_drops"
                            name="webhook_price_drops"
                            type="checkbox"
                            value="1"
                            @checked(old('webhook_price_drops', $siteSettings->webhook_price_drops ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Снижение цен</span>
                    </label>
                    <p class="ml-6 text-xs text-slate-500">Отправлять вебхук при снижении цены ниже целевой.</p>

                    <label class="flex items-center gap-2">
                        <input
                            id="webhook_errors"
                            name="webhook_errors"
                            type="checkbox"
                            value="1"
                            @checked(old('webhook_errors', $siteSettings->webhook_errors ?? false))
                            class="h-4 w-4 rounded border-gray-300 text-blue-700"
                        >
                        <span class="text-sm font-medium">Ошибки импорта</span>
                    </label>
                    <p class="ml-6 text-xs text-slate-500">Отправлять вебхук при ошибках парсинга.</p>
                </div>

                <hr class="border-slate-200">

                <!-- Webhook Secret -->
                <div>
                    <label for="webhook_secret" class="block text-sm font-medium">Секретный ключ вебхука</label>
                    <div class="flex gap-2">
                        <input
                            id="webhook_secret"
                            type="text"
                            readonly
                            value="{{ old('webhook_secret', $siteSettings->webhook_secret ?? '') }}"
                            class="mt-1 flex-1 rounded border bg-slate-50 p-2 font-mono text-sm"
                        >
                        <button type="submit" name="regenerate_webhook_secret" value="1" class="mt-1 rounded bg-slate-200 px-3 py-2 text-sm font-medium hover:bg-slate-300 whitespace-nowrap">Обновить</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">HMAC-секрет для подписи вебхук-запросов. Используйте для проверки подлинности входящих запросов.</p>
                </div>

                @error('webhook_url')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки API и вебхуков</button>
            </form>
        </section>

        <!-- Localization Settings -->
        <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-6 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Локализация и язык</h1>
                <p class="text-sm text-slate-500">Язык сайта, формат дат, времени и чисел.</p>
            </header>
            <form method="POST" action="{{ route('admin.settings.localization.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Language -->
                <div>
                    <label for="locale_language" class="block text-sm font-medium">Язык сайта</label>
                    <select
                        id="locale_language"
                        name="locale_language"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="ru" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'ru')>Русский</option>
                        <option value="en" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'en')>English</option>
                        <option value="fr" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'fr')>Français</option>
                        <option value="de" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'de')>Deutsch</option>
                        <option value="es" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'es')>Español</option>
                        <option value="zh" @selected(old('locale_language', $siteSettings->locale_language ?? 'ru') === 'zh')>中文</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Основной язык интерфейса сайта.</p>
                </div>

                <!-- Date Format -->
                <div>
                    <label for="locale_date_format" class="block text-sm font-medium">Формат дат</label>
                    <select
                        id="locale_date_format"
                        name="locale_date_format"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="d.m.Y" @selected(old('locale_date_format', $siteSettings->locale_date_format ?? 'd.m.Y') === 'd.m.Y')>DD.MM.YYYY (13.11.2026)</option>
                        <option value="m/d/Y" @selected(old('locale_date_format', $siteSettings->locale_date_format ?? 'd.m.Y') === 'm/d/Y')>MM/DD/YYYY (11/13/2026)</option>
                        <option value="Y-m-d" @selected(old('locale_date_format', $siteSettings->locale_date_format ?? 'd.m.Y') === 'Y-m-d')>YYYY-MM-DD (2026-11-13)</option>
                        <option value="d/m/Y" @selected(old('locale_date_format', $siteSettings->locale_date_format ?? 'd.m.Y') === 'd/m/Y')>DD/MM/YYYY (13/11/2026)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Формат отображения дат на сайте.</p>
                </div>

                <!-- Time Format -->
                <div>
                    <label for="locale_time_format" class="block text-sm font-medium">Формат времени</label>
                    <select
                        id="locale_time_format"
                        name="locale_time_format"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="24h" @selected(old('locale_time_format', $siteSettings->locale_time_format ?? '24h') === '24h')>24-часовой (14:30)</option>
                        <option value="12h" @selected(old('locale_time_format', $siteSettings->locale_time_format ?? '24h') === '12h')>12-часовой (2:30 PM)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Формат отображения времени на сайте.</p>
                </div>

                <!-- Number Format -->
                <div>
                    <label for="locale_number_format" class="block text-sm font-medium">Формат чисел</label>
                    <select
                        id="locale_number_format"
                        name="locale_number_format"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="space_dot" @selected(old('locale_number_format', $siteSettings->locale_number_format ?? 'space_dot') === 'space_dot')>1 000.50 (пробел и точка)</option>
                        <option value="comma_dot" @selected(old('locale_number_format', $siteSettings->locale_number_format ?? 'space_dot') === 'comma_dot')>1,000.50 (запятая и точка)</option>
                        <option value="dot_comma" @selected(old('locale_number_format', $siteSettings->locale_number_format ?? 'space_dot') === 'dot_comma')>1.000,50 (точка и запятая)</option>
                        <option value="space_comma" @selected(old('locale_number_format', $siteSettings->locale_number_format ?? 'space_dot') === 'space_comma')>1 000,50 (пробел и запятая)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Формат отображения чисел и цен на сайте.</p>
                </div>

                <!-- First Day of Week -->
                <div>
                    <label for="locale_first_day" class="block text-sm font-medium">Первый день недели</label>
                    <select
                        id="locale_first_day"
                        name="locale_first_day"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="monday" @selected(old('locale_first_day', $siteSettings->locale_first_day ?? 'monday') === 'monday')>Понедельник</option>
                        <option value="sunday" @selected(old('locale_first_day', $siteSettings->locale_first_day ?? 'monday') === 'sunday')>Воскресенье</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Первый день недели в календарях и таблицах.</p>
                </div>

                <!-- Text Direction -->
                <div>
                    <label for="locale_direction" class="block text-sm font-medium">Направление текста</label>
                    <select
                        id="locale_direction"
                        name="locale_direction"
                        class="mt-1 w-full rounded border p-2"
                    >
                        <option value="ltr" @selected(old('locale_direction', $siteSettings->locale_direction ?? 'ltr') === 'ltr')>Слева направо (LTR)</option>
                        <option value="rtl" @selected(old('locale_direction', $siteSettings->locale_direction ?? 'ltr') === 'rtl')>Справа налево (RTL)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Направление текста для арабского, иврита и других языков.</p>
                </div>

                @error('locale_language')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки локализации</button>
            </form>
        </section>

    </div>
</x-admin-layout>
