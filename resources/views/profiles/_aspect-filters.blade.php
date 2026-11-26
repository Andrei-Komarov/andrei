@php
    $aspectFields = [
        'brand' => 'Бренд',
        'processor' => 'Процессор',
        'ram_size' => 'Объём RAM',
        'storage_capacity' => 'Накопитель',
        'screen_size' => 'Размер экрана',
        'gpu' => 'Видеокарта',
        'operating_system' => 'Операционная система',
    ];
    $multipleFields = ['brand', 'processor', 'ram_size', 'storage_capacity', 'screen_size', 'gpu'];
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-ebay-filter-options data-options-url="{{ route('profiles.filter-options') }}">
    @foreach ($aspectFields as $field => $label)
        @php
            $savedValue = old($field, $filterValues[$field] ?? '');
            $savedValues = is_array($savedValue) ? array_values($savedValue) : (filled($savedValue) ? [$savedValue] : []);
            $multiple = in_array($field, $multipleFields, true);
            $savedValues = array_values(array_filter($savedValues, fn (mixed $value): bool => $value !== '__any__'));
        @endphp
        <label class="text-sm font-medium">
            {{ $label }}
            <select
                id="{{ $field }}"
                name="{{ $multiple ? $field.'[]' : $field }}"
                data-ebay-aspect="{{ $field }}"
                data-selected="{{ json_encode($savedValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                @if ($multiple) multiple size="10" @endif
                disabled
                class="mt-1 w-full rounded border p-2 font-normal"
            >
                @if ($multiple)
                    <option value="__any__" data-any-option @selected($savedValues === [])>Любой</option>
                @else
                    <option value="" @selected($savedValues === [])>Любое значение</option>
                @endif
                @foreach ($savedValues as $savedValue)
                    <option value="{{ $savedValue }}" selected>{{ $savedValue }} (сохранённое значение)</option>
                @endforeach
            </select>
            @if ($multiple)
                <span class="mt-1 block text-xs font-normal text-slate-500">Чтобы выбрать несколько вариантов, удерживайте Ctrl или Cmd.</span>
            @endif
            @error($field)<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            @error("{$field}.*")<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
    @endforeach
    <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3" data-ebay-filter-status role="status" aria-live="polite">
        Загружаются варианты фильтров для категории eBay «Ноутбуки».
    </p>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const container = document.querySelector('[data-ebay-filter-options]');

            if (!container) {
                return;
            }

            const status = container.querySelector('[data-ebay-filter-status]');

            try {
                const response = await fetch(container.dataset.optionsUrl, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Не удалось загрузить варианты eBay.');
                }

                for (const select of container.querySelectorAll('[data-ebay-aspect]')) {
                    const field = select.dataset.ebayAspect;
                    const aspect = payload.filters?.[field];
                    const selectedValues = JSON.parse(select.dataset.selected || '[]');
                    select.replaceChildren();

                    if (select.multiple) {
                        const anyOption = new Option('Любой', '__any__');
                        anyOption.selected = selectedValues.length === 0;
                        anyOption.dataset.anyOption = '';
                        select.add(anyOption);
                    } else {
                        const anyOption = new Option('Любое значение', '');
                        anyOption.selected = selectedValues.length === 0;
                        select.add(anyOption);
                    }

                    for (const value of aspect?.values || []) {
                        const option = new Option(value, value);
                        option.selected = selectedValues.includes(value);
                        select.add(option);
                    }

                    for (const selectedValue of selectedValues) {
                        if (!Array.from(select.options).some((option) => option.value === selectedValue)) {
                            const savedOption = new Option(`${selectedValue} (сохранённое значение)`, selectedValue);
                            savedOption.selected = true;
                            select.add(savedOption);
                        }
                    }

                    if (!select.multiple) {
                        select.value = selectedValues[0] || '';
                    } else {
                        select.addEventListener('change', () => {
                            const anyOption = select.querySelector('[data-any-option]');
                            const selectedOptions = Array.from(select.selectedOptions);

                            if (anyOption?.selected) {
                                for (const option of select.options) {
                                    option.selected = option === anyOption;
                                }
                            } else if (selectedOptions.length === 0) {
                                anyOption.selected = true;
                            } else {
                                anyOption.selected = false;
                            }
                        });
                    }
                    select.disabled = !aspect?.values?.length && selectedValues.length === 0;

                    if (!aspect?.values?.length) {
                        const savedOptions = selectedValues.map((value) => {
                            const option = new Option(`${value} (сохранённое значение)`, value);
                            option.selected = true;
                            return option;
                        });
                        select.replaceChildren(...(savedOptions.length > 0
                            ? savedOptions
                            : [new Option('Нет вариантов для категории', '')]));
                        select.disabled = selectedValues.length === 0;
                    }
                }
                status.textContent = 'Варианты загружены из фильтров категорий ноутбуков eBay.';
            } catch (error) {
                for (const select of container.querySelectorAll('[data-ebay-aspect]')) {
                    select.disabled = false;
                }
                status.textContent = error.message;
                status.classList.add('text-red-700');
            }
        });
    </script>
@endonce
