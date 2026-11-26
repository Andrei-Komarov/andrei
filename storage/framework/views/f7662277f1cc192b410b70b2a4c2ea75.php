<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Запчасти для расчёта']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Запчасти для расчёта']); ?>
    <div class="space-y-4">
        <?php if(session('success')): ?>
            <div class="rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="rounded bg-red-100 p-3 text-red-800">
                <p class="font-medium">Проверьте заполненные поля.</p>
                <ul class="mt-1 list-inside list-disc">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="rounded-xl bg-white p-4 shadow-sm">
            <header class="mb-4">
                <h1 class="text-2xl font-bold">Запчасти для расчёта</h1>
                <p class="text-sm text-slate-500">Задайте название и цену. Активные запчасти появятся в калькуляторе рентабельности.</p>
            </header>
            <form method="POST" action="<?php echo e(route('admin.profitability-parts.store')); ?>" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                <?php echo csrf_field(); ?>
                <label for="new-part-name" class="text-sm font-medium">
                    Название запчасти
                    <input id="new-part-name" name="name" value="<?php echo e(old('name')); ?>" required maxlength="255" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
                </label>
                <label for="new-part-price" class="text-sm font-medium">
                    Цена, сом
                    <input id="new-part-price" name="price" type="number" min="0" max="99999999.99" step="0.01" value="<?php echo e(old('price')); ?>" required class="mt-1 w-full rounded border border-slate-300 p-2 font-normal">
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
                        <?php $__empty_1 = true; $__currentLoopData = $parts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="p-3">
                                    <form id="update-part-<?php echo e($part->id); ?>" method="POST" action="<?php echo e(route('admin.profitability-parts.update', $part)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input name="name" value="<?php echo e($part->name); ?>" maxlength="255" required class="w-full min-w-48 rounded border border-slate-300 p-2">
                                    </form>
                                </td>
                                <td class="p-3">
                                    <input form="update-part-<?php echo e($part->id); ?>" name="price" type="number" min="0" max="99999999.99" step="0.01" value="<?php echo e($part->price); ?>" required class="w-36 rounded border border-slate-300 p-2">
                                </td>
                                <td class="p-3">
                                    <select form="update-part-<?php echo e($part->id); ?>" name="is_active" class="rounded border border-slate-300 p-2">
                                        <option value="1" <?php if($part->is_active): echo 'selected'; endif; ?>>Активна</option>
                                        <option value="0" <?php if(! $part->is_active): echo 'selected'; endif; ?>>Скрыта</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <button form="update-part-<?php echo e($part->id); ?>" class="text-xs font-medium text-blue-700 hover:underline">Сохранить</button>
                                        <?php if($part->is_active): ?>
                                            <form method="POST" action="<?php echo e(route('admin.profitability-parts.destroy', $part)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="text-xs font-medium text-red-700 hover:underline">Скрыть</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="<?php echo e(route('admin.profitability-parts.force-delete', $part)); ?>" onsubmit="return confirm('Вы уверены, что хотите удалить эту запчасть? Это действие нельзя отменить.')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="text-xs font-medium text-red-500 hover:text-red-700">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="p-6 text-center text-slate-500">Список запчастей пока пуст.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4"><?php echo e($parts->links()); ?></div>
        </section>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $attributes = $__attributesOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $component = $__componentOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__componentOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/profitability-parts.blade.php ENDPATH**/ ?>