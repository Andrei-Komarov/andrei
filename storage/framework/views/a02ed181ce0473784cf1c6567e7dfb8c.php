<?php
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
?>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-ebay-filter-options data-options-url="<?php echo e(route('profiles.filter-options')); ?>">
    <?php $__currentLoopData = $aspectFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $savedValue = old($field, $filterValues[$field] ?? '');
            $savedValues = is_array($savedValue) ? array_values($savedValue) : (filled($savedValue) ? [$savedValue] : []);
            $multiple = in_array($field, $multipleFields, true);
            $savedValues = array_values(array_filter($savedValues, fn (mixed $value): bool => $value !== '__any__'));
        ?>
        <label class="text-sm font-medium">
            <?php echo e($label); ?>

            <select
                id="<?php echo e($field); ?>"
                name="<?php echo e($multiple ? $field.'[]' : $field); ?>"
                data-ebay-aspect="<?php echo e($field); ?>"
                data-selected="<?php echo e(json_encode($savedValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"
                <?php if($multiple): ?> multiple size="10" <?php endif; ?>
                disabled
                class="mt-1 w-full rounded border p-2 font-normal"
            >
                <?php if($multiple): ?>
                    <option value="__any__" data-any-option <?php if($savedValues === []): echo 'selected'; endif; ?>>Любой</option>
                <?php else: ?>
                    <option value="" <?php if($savedValues === []): echo 'selected'; endif; ?>>Любое значение</option>
                <?php endif; ?>
                <?php $__currentLoopData = $savedValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $savedValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($savedValue); ?>" selected><?php echo e($savedValue); ?> (сохранённое значение)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($multiple): ?>
                <span class="mt-1 block text-xs font-normal text-slate-500">Чтобы выбрать несколько вариантов, удерживайте Ctrl или Cmd.</span>
            <?php endif; ?>
            <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="mt-1 block text-sm text-red-700"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php $__errorArgs = ["{$field}.*"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="mt-1 block text-sm text-red-700"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </label>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3" data-ebay-filter-status role="status" aria-live="polite">
        Загружаются варианты фильтров для категории eBay «Ноутбуки».
    </p>
</div>

<?php if (! $__env->hasRenderedOnce('e6af7bd1-249c-4941-9489-1644312695fb')): $__env->markAsRenderedOnce('e6af7bd1-249c-4941-9489-1644312695fb'); ?>
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
<?php endif; ?>
<?php /**PATH /home/andrei/web/ebay/resources/views/profiles/_aspect-filters.blade.php ENDPATH**/ ?>