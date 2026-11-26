<x-admin-layout title="Резервное копирование">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Резервное копирование</h1>
            <p class="text-sm text-slate-500">Настройки автоматического резервного копирования базы данных и файлов.</p>
        </header>

        <form method="POST" action="{{ route('admin.backup.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Schedule -->
            <div>
                <label for="backup_schedule" class="block text-sm font-medium">Расписание</label>
                <select
                    id="backup_schedule"
                    name="backup_schedule"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                    <option value="manual" @selected(old('backup_schedule', $siteSettings->backup_schedule ?? 'daily') === 'manual')>Вручную</option>
                    <option value="daily" @selected(old('backup_schedule', $siteSettings->backup_schedule ?? 'daily') === 'daily')>Ежедневно</option>
                    <option value="weekly" @selected(old('backup_schedule', $siteSettings->backup_schedule ?? 'daily') === 'weekly')>Еженедельно</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Как часто выполнять автоматическое резервное копирование.</p>
            </div>

            <!-- Retain Count -->
            <div>
                <label for="backup_retain_count" class="block text-sm font-medium">Сохранять бэкапов</label>
                <input
                    id="backup_retain_count"
                    name="backup_retain_count"
                    type="number"
                    min="1"
                    max="365"
                    value="{{ old('backup_retain_count', $siteSettings->backup_retain_count ?? 7) }}"
                    class="mt-1 w-full rounded border p-2 sm:max-w-xs"
                >
                <p class="mt-1 text-xs text-slate-500">Количество последних бэкапов для хранения, старые будут удалены автоматически.</p>
            </div>

            <hr class="border-slate-200">

            <!-- Backup Database -->
            <div class="flex items-center gap-3">
                <input
                    id="backup_database"
                    name="backup_database"
                    type="checkbox"
                    value="1"
                    @checked(old('backup_database', $siteSettings->backup_database ?? true) === true)
                    class="h-4 w-4 rounded border-slate-300 text-blue-700"
                >
                <label for="backup_database" class="text-sm font-medium">Бэкап базы данных</label>
            </div>
            <p class="text-xs text-slate-500">Создавать дамп базы данных PostgreSQL.</p>

            <!-- Backup Files -->
            <div class="flex items-center gap-3">
                <input
                    id="backup_files"
                    name="backup_files"
                    type="checkbox"
                    value="1"
                    @checked(old('backup_files', $siteSettings->backup_files ?? true) === true)
                    class="h-4 w-4 rounded border-slate-300 text-blue-700"
                >
                <label for="backup_files" class="text-sm font-medium">Бэкап файлов</label>
            </div>
            <p class="text-xs text-slate-500">Архивировать загруженные файлы (логотипы, favicon и т.д.).</p>

            <!-- Backup Path -->
            <div>
                <label for="backup_path" class="block text-sm font-medium">Путь к бэкапам</label>
                <input
                    id="backup_path"
                    name="backup_path"
                    type="text"
                    maxlength="512"
                    value="{{ old('backup_path', $siteSettings->backup_path ?? '') }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Абсолютный путь к папке для бэкапов. По умолчанию: <code>backups/</code> в корне проекта.</p>
            </div>

            <hr class="border-slate-200">

            <!-- Notify Telegram -->
            <div class="flex items-center gap-3">
                <input
                    id="backup_notify_telegram"
                    name="backup_notify_telegram"
                    type="checkbox"
                    value="1"
                    @checked(old('backup_notify_telegram', $siteSettings->backup_notify_telegram ?? true) === true)
                    class="h-4 w-4 rounded border-slate-300 text-blue-700"
                >
                <label for="backup_notify_telegram" class="text-sm font-medium">Уведомлять в Telegram</label>
            </div>
            <p class="text-xs text-slate-500">Отправлять уведомление в Telegram об успешном или неудачном бэкапе.</p>

            @error('backup_schedule')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('backup_retain_count')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('backup_path')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки</button>
        </form>

        <form method="POST" action="{{ route('admin.backup.run') }}" class="mt-6">
            @csrf
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Создать бэкап сейчас</button>
        </form>
    </section>
</x-admin-layout>
