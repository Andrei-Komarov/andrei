<x-admin-layout title="Планировщик задач">
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

        {{-- Cron status --}}
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Планировщик задач</h1>
                <p class="text-sm text-slate-500">Мониторинг и управление фоновыми задачами импорта с eBay.</p>
            </header>

            <div class="flex items-start gap-4 rounded-lg border p-4"
                @class(['border-emerald-200 bg-emerald-50' => $cronStatus['configured'], 'border-amber-200 bg-amber-50' => ! $cronStatus['configured']])>
                <span class="mt-0.5 text-xl" @class(['text-emerald-600' => $cronStatus['configured'], 'text-amber-600' => ! $cronStatus['configured']])>
                    {{ $cronStatus['configured'] ? '✔' : '⚠' }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" @class(['text-emerald-800' => $cronStatus['configured'], 'text-amber-800' => ! $cronStatus['configured']])>
                        {{ $cronStatus['configured'] ? 'Cron настроен и работает' : 'Cron не обнаружен' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-600">
                        @if ($cronStatus['configured'])
                            Команда <code class="rounded bg-white/60 px-1 font-mono">php artisan schedule:run</code> найдена в crontab.
                        @else
                            Добавьте <code class="rounded bg-white/60 px-1 font-mono">*</code> * * * * cd /path/to/project && php artisan schedule:run</code> в crontab сервера.
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <form method="POST" action="{{ route('admin.scheduler.run-import') }}">
                    @csrf
                    <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        Запустить запланированные импорты
                    </button>
                </form>
            </div>
        </section>

        {{-- Schedule configuration --}}
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h2 class="text-lg font-bold">Конфигурация расписания</h2>
                <p class="text-sm text-slate-500">Настройки планировщика для команды <code class="rounded bg-slate-100 px-1 font-mono text-xs">ebay:import-laptops --scheduled</code>.</p>
            </header>

            <form method="POST" action="{{ route('admin.scheduler.schedule.update') }}" class="space-y-4">
                @csrf

                <div>
                    <span class="block text-sm font-medium text-slate-700">Тип планировщика</span>
                    <div class="mt-1 flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="schedule_type" value="scheduled" {{ $settings->schedule_type === 'cron' ? '' : 'checked' }} class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500" onchange="document.getElementById('scheduledSettings').classList.toggle('hidden', false); document.getElementById('cronInfo').classList.toggle('hidden', true);">
                            <span class="text-sm text-slate-700">Laravel Schedule</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="schedule_type" value="cron" {{ $settings->schedule_type === 'cron' ? 'checked' : '' }} class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500" onchange="document.getElementById('scheduledSettings').classList.toggle('hidden', true); document.getElementById('cronInfo').classList.toggle('hidden', false);">
                            <span class="text-sm text-slate-700">Системный Cron</span>
                        </label>
                    </div>
                </div>

                <div id="scheduledSettings" class="{{ $settings->schedule_type === 'cron' ? 'hidden' : '' }} space-y-4">
                    <div>
                        <label for="schedule_frequency" class="block text-sm font-medium text-slate-700">Частота выполнения</label>
                        <select name="schedule_frequency" id="schedule_frequency" class="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="everyMinute" {{ $settings->schedule_frequency === 'everyMinute' ? 'selected' : '' }}>Каждую минуту</option>
                            <option value="everyFiveMinutes" {{ $settings->schedule_frequency === 'everyFiveMinutes' ? 'selected' : '' }}>Каждые 5 минут</option>
                            <option value="everyTenMinutes" {{ $settings->schedule_frequency === 'everyTenMinutes' ? 'selected' : '' }}>Каждые 10 минут</option>
                            <option value="everyFifteenMinutes" {{ $settings->schedule_frequency === 'everyFifteenMinutes' ? 'selected' : '' }}>Каждые 15 минут</option>
                            <option value="everyThirtyMinutes" {{ $settings->schedule_frequency === 'everyThirtyMinutes' ? 'selected' : '' }}>Каждые 30 минут</option>
                            <option value="hourly" {{ $settings->schedule_frequency === 'hourly' ? 'selected' : '' }}>Каждый час</option>
                        </select>
                    </div>

                    <div>
                        <label for="schedule_timezone" class="block text-sm font-medium text-slate-700">Часовой пояс</label>
                        <select name="schedule_timezone" id="schedule_timezone" class="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone }}" {{ $settings->schedule_timezone === $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="schedule_without_overlapping" id="schedule_without_overlapping" value="1" {{ $settings->schedule_without_overlapping ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="schedule_without_overlapping" class="text-sm text-slate-700">Запускать без перекрытия (withoutOverlapping)</label>
                    </div>
                    <p class="text-xs text-slate-500">Если включено, новая задача не начнётся, если предыдущая ещё выполняется.</p>
                </div>

                <div id="cronInfo" class="{{ $settings->schedule_type === 'cron' ? '' : 'hidden' }} rounded-lg border border-amber-200 bg-amber-50 p-4">
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

        {{-- Profiles with schedules --}}
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h2 class="text-lg font-bold">Профили с расписанием</h2>
                <p class="text-sm text-slate-500">Все активные поисковые профили и их параметры планирования.</p>
            </header>

            @if ($profiles->isEmpty())
                <p class="text-sm text-slate-500">Поисковые профили не найдены.</p>
            @else
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
                            @foreach ($profiles as $profile)
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="py-2.5 pr-3 text-slate-500">#{{ $profile['id'] }}</td>
                                    <td class="py-2.5 pr-3 font-medium">{{ $profile['name'] }}</td>
                                    <td class="py-2.5 pr-3 text-slate-600">{{ $profile['user_name'] }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span class="rounded px-2 py-0.5 text-xs font-medium"
                                            @class(['bg-emerald-100 text-emerald-700' => $profile['active'], 'bg-slate-100 text-slate-500' => ! $profile['active']])>
                                            {{ $profile['active'] ? 'Активен' : 'Неактивен' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-3 font-mono text-xs">{{ $profile['schedule_time'] ?? '—' }}</td>
                                    <td class="py-2.5 pr-3">{{ $profile['schedule_interval_hours'] ? $profile['schedule_interval_hours'].' ч' : '—' }}</td>
                                    <td class="py-2.5 pr-3 text-xs text-slate-500">
                                        {{ $profile['last_scheduled_at'] ? $profile['last_scheduled_at']->diffForHumans() : '—' }}
                                    </td>
                                    <td class="py-2.5 text-xs text-slate-500">
                                        {{ $profile['last_synced_at'] ? $profile['last_synced_at']->diffForHumans() : '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="py-1.5 pl-2">
                                        <form method="POST" action="{{ route('admin.scheduler.run-import') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="profile_id" value="{{ $profile['id'] }}">
                                            <button type="submit" class="text-xs text-blue-700 hover:underline">
                                                Запустить импорт для этого профиля
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>
</x-admin-layout>
