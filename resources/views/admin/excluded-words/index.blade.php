<x-admin-layout title="Слова исключения">
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
                <h1 class="text-2xl font-bold">Слова исключения</h1>
                <p class="text-sm text-slate-500">
                    При парсинге товаров система проверяет название и описание продавца на наличие этих слов.
                    Если совпадение найдено, товар помечается как исключённый и отображается в разделе «Исключённый товар».
                </p>
            </header>
            <form method="POST" action="{{ route('admin.excluded-words.store') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                @csrf
                <label for="new-word" class="text-sm font-medium">
                    Слово или фраза
                    <input id="new-word" name="word" value="{{ old('word') }}" required maxlength="255" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal" placeholder="Например: replica, копия, тест">
                </label>
                <div class="flex items-end">
                    <button class="rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Добавить</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="data-table text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-3">Слово</th>
                            <th class="p-3">Статус</th>
                            <th class="p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($words as $word)
                            <tr>
                                <td class="p-3">
                                    <form id="update-word-{{ $word->id }}" method="POST" action="{{ route('admin.excluded-words.update', $word) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input name="word" value="{{ $word->word }}" maxlength="255" required class="w-full min-w-48 rounded border border-slate-300 p-2">
                                    </form>
                                </td>
                                <td class="p-3">
                                    <select form="update-word-{{ $word->id }}" name="active" class="rounded border border-slate-300 p-2">
                                        <option value="1" @selected($word->active)>Активно</option>
                                        <option value="0" @selected(! $word->active)>Неактивно</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <button form="update-word-{{ $word->id }}" class="text-xs font-medium text-blue-700 hover:underline">Сохранить</button>
                                        <form method="POST" action="{{ route('admin.excluded-words.destroy', $word) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-medium text-red-700 hover:underline">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-500">Список исключённых слов пока пуст.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $words->links() }}</div>
        </section>
    </div>
</x-admin-layout>
