<x-admin-layout title="Пользователи и роли">
    <div class="min-w-0">
                @if (session('success'))
                    <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <section class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Пользователей</p><p class="mt-1 text-2xl font-bold">{{ $userCount }}</p></div>
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Профилей поиска</p><p class="mt-1 text-2xl font-bold">{{ $profileCount }}</p></div>
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Записей истории</p><p class="mt-1 text-2xl font-bold">{{ $activityCount }}</p></div>
                </section>

                <section class="rounded-xl bg-white shadow-sm">
                    <header class="border-b border-slate-200 p-4">
                        <h1 class="text-2xl font-bold">Пользователи и роли</h1>
                        <p class="text-sm text-slate-500">Управление пользователями, назначение ролей и статистика сайта.</p>
                    </header>
                    <div class="border-b border-slate-200 p-4">
                        <h2 class="font-semibold">Пользователи</h2>
                        <p class="mt-1 text-sm text-slate-500">Назначайте администраторами доверенных пользователей.</p>
                    </div>
                    @include('partials.table-column-settings', ['tableKey' => 'admin-users'])
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    @include('partials.sortable-table-heading', ['columnKey' => 'name', 'sortKey' => 'name', 'label' => 'Имя', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'email', 'sortKey' => 'email', 'label' => 'Email', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'registered', 'sortKey' => 'registered', 'label' => 'Регистрация', 'sort' => $sort])
                                    @include('partials.sortable-table-heading', ['columnKey' => 'role', 'sortKey' => 'role', 'label' => 'Роль', 'sort' => $sort])
                                    <th class="p-3" data-column-key="actions">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="p-3 font-medium">{{ $user->name }}</td>
                                        <td class="p-3">{{ $user->email }}</td>
                                        <td class="p-3 text-slate-600">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                        <td class="p-3">
                                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_admin" value="{{ $user->isAdmin() ? 0 : 1 }}">
                                                <span class="text-xs {{ $user->isAdmin() ? 'text-blue-700' : 'text-slate-500' }}">{{ $user->isAdmin() ? 'Администратор' : 'Пользователь' }}</span>
                                                @if (! $user->isAdmin())
                                                    <button class="text-xs font-medium text-blue-700 hover:underline">Назначить администратором</button>
                                                @elseif (auth('admin')->id() !== $user->id && mb_strtolower($user->email) !== mb_strtolower((string) config('services.admin.email')))
                                                    <button class="text-xs font-medium text-red-700 hover:underline" onclick="this.form.is_admin.value = 0">Снять роль</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-medium text-blue-700 hover:underline">Редактировать</a>
                                                @if (auth('admin')->id() !== $user->id && mb_strtolower($user->email) !== mb_strtolower((string) config('services.admin.email')))
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                        data-confirm-dialog-form
                                                        data-confirm-button="Удалить пользователя"
                                                        data-confirm-message="Удалить пользователя {{ $user->name }}? Его профили и связанные товары также будут удалены, история действий сохранится.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="text-xs font-medium text-red-700 hover:underline">Удалить</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $users->links() }}</div>
                </section>
    </div>
    @include('partials.confirm-dialog')
</x-admin-layout>
