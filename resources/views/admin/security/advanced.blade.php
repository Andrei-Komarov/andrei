<x-admin-layout title="Расширенная безопасность">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <header class="mb-6 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-bold">Расширенная безопасность</h1>
            <p class="text-sm text-slate-500">IP-контроль, защита от ботов и контроль загрузки файлов.</p>
        </header>

        <form method="POST" action="{{ route('admin.security.advanced.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- IP Blacklist -->
            <div>
                <label for="security_ip_blacklist" class="block text-sm font-medium">IP-черный список</label>
                <textarea
                    id="security_ip_blacklist"
                    name="security_ip_blacklist"
                    rows="3"
                    placeholder="192.168.1.100, 10.0.0.50, 172.16.0.1"
                    class="mt-1 w-full rounded border p-2"
                >{{ old('security_ip_blacklist', $siteSettings->security_ip_blacklist ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Список IP-адресов для блокировки доступа. Укажите через запятую, по одному на строку.</p>
            </div>

            <!-- IP Whitelist -->
            <div>
                <label for="security_ip_whitelist" class="block text-sm font-medium">IP-белый список</label>
                <textarea
                    id="security_ip_whitelist"
                    name="security_ip_whitelist"
                    rows="3"
                    placeholder="192.168.1.1, 10.0.0.1"
                    class="mt-1 w-full rounded border p-2"
                >{{ old('security_ip_whitelist', $siteSettings->security_ip_whitelist ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Список IP-адресов, которым разрешён доступ. Работает только когда включён переключатель ниже.</p>
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="security_ip_whitelist_enabled"
                    name="security_ip_whitelist_enabled"
                    type="checkbox"
                    value="1"
                    @checked(old('security_ip_whitelist_enabled', $siteSettings->security_ip_whitelist_enabled ?? false))
                    class="h-4 w-4 rounded border-gray-300 text-blue-700"
                >
                <label for="security_ip_whitelist_enabled" class="text-sm font-medium">Включить IP-белый список</label>
            </div>
            <p class="text-xs text-slate-500">Когда включён, доступ к сайту разрешён только IP-адресам из белого списка.</p>

            <hr class="border-slate-200">

            <!-- Blocked User Agents -->
            <div>
                <label for="security_blocked_user_agents" class="block text-sm font-medium">Блокируемые User-Agent</label>
                <textarea
                    id="security_blocked_user_agents"
                    name="security_blocked_user_agents"
                    rows="3"
                    placeholder="Scrapy, python-requests, curl"
                    class="mt-1 w-full rounded border p-2"
                >{{ old('security_blocked_user_agents', $siteSettings->security_blocked_user_agents ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Список User-Agent строк для блокировки. Каждая строка — отдельный User-Agent.</p>
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="security_honeypot_enabled"
                    name="security_honeypot_enabled"
                    type="checkbox"
                    value="1"
                    @checked(old('security_honeypot_enabled', $siteSettings->security_honeypot_enabled ?? true))
                    class="h-4 w-4 rounded border-gray-300 text-blue-700"
                >
                <label for="security_honeypot_enabled" class="text-sm font-medium">Включить Honeypot защиту</label>
            </div>
            <p class="text-xs text-slate-500">Скрытое поле в формах для защиты от спам-ботов. Боты заполняют это поле, а люди — нет.</p>

            <hr class="border-slate-200">

            <!-- Max Upload Size -->
            <div>
                <label for="security_max_upload_size" class="block text-sm font-medium">Максимальный размер загрузки (МБ)</label>
                <input
                    id="security_max_upload_size"
                    name="security_max_upload_size"
                    type="number"
                    min="1"
                    max="1024"
                    value="{{ old('security_max_upload_size', $siteSettings->security_max_upload_size ?? 10) }}"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Максимальный размер загружаемого файла в мегабайтах.</p>
            </div>

            <!-- Allowed File Types -->
            <div>
                <label for="security_allowed_file_types" class="block text-sm font-medium">Разрешённые типы файлов</label>
                <input
                    id="security_allowed_file_types"
                    name="security_allowed_file_types"
                    type="text"
                    value="{{ old('security_allowed_file_types', $siteSettings->security_allowed_file_types ?? 'jpg,jpeg,png,gif,pdf,doc,docx') }}"
                    placeholder="jpg,jpeg,png,gif,pdf,doc,docx"
                    class="mt-1 w-full rounded border p-2"
                >
                <p class="mt-1 text-xs text-slate-500">Список разрешённых расширений файлов для загрузки. Укажите через запятую.</p>
            </div>

            @error('security_max_upload_size')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Сохранить настройки</button>
        </form>
    </section>
</x-admin-layout>
