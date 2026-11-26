<x-admin-layout title="Запчасти для расчёта">
    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded bg-emerald-100 p-3 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800">
                <p class="font-medium">Проверьте заполненные поля.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl bg-white p-4 shadow-sm">
            <header class="mb-4">
                <h1 class="text-2xl font-bold">Запчасти для расчёта</h1>
                <p class="text-sm text-slate-500">Задайте название и цену. Активные запчасти появятся в калькуляторе рентабельности.</p>
            </header>
            <form method="POST" action="{{ route('admin.profitability-parts.store') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                @csrf
                <label for="new-part-name" class="text-sm font-medium">
                    Название запчасти
                    <input id="new-part-name" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <label for="new-part-price" class="text-sm font-medium">
                    Цена, сом
                    <input id="new-part-price" name="price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('price') }}" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <div class="flex items-end">
                    <button class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Добавить запчасть</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-3">Название</th>
                            <th class="p-3">Цена, сом</th>
                            <th class="p-3">Статус</th>
                            <th class="p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($parts as $part)
                            <tr>
                                <td class="p-3">
                                    <form id="update-part-{{ $part->id }}" method="POST" action="{{ route('admin.profitability-parts.update', $part) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input name="name" value="{{ $part->name }}" maxlength="255" required class="w-full min-w-48 rounded border border-slate-300 p-2">
                                    </form>
                                </td>
                                <td class="p-3">
                                    <input form="update-part-{{ $part->id }}" name="price" type="number" min="0" max="99999999.99" step="0.01" value="{{ $part->price }}" required class="w-36 rounded border border-slate-300 p-2">
                                </td>
                                <td class="p-3">
                                    <select form="update-part-{{ $part->id }}" name="is_active" class="rounded border border-slate-300 p-2">
                                        <option value="1" @selected($part->is_active)>Активна</option>
                                        <option value="0" @selected(! $part->is_active)>Скрыта</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <button form="update-part-{{ $part->id }}" class="text-xs font-medium text-blue-700 hover:underline">Сохранить</button>
                                        @if ($part->is_active)
                                            <form method="POST" action="{{ route('admin.profitability-parts.destroy', $part) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-xs font-medium text-red-700 hover:underline">Скрыть</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.profitability-parts.force-delete', $part) }}" onsubmit="return confirm('Вы уверены, что хотите удалить эту запчасть? Это действие нельзя отменить.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-medium text-red-500 hover:text-red-700">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-slate-500">Список запчастей пока пуст.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $parts->links() }}</div>
        </section>
    </div>
</x-admin-layout>
