<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Слова исключения']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Слова исключения']); ?>
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
                <h1 class="text-2xl font-bold">Слова исключения</h1>
                <p class="text-sm text-slate-500">
                    При парсинге товаров система проверяет название и описание продавца на наличие этих слов.
                    Если совпадение найдено, товар помечается как исключённый и отображается в разделе «Исключённый товар».
                </p>
            </header>
            <form method="POST" action="<?php echo e(route('admin.excluded-words.store')); ?>" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                <?php echo csrf_field(); ?>
                <label for="new-word" class="text-sm font-medium">
                    Слово или фраза
                    <input id="new-word" name="word" value="<?php echo e(old('word')); ?>" required maxlength="255" class="mt-1 w-full rounded border border-slate-300 p-2 font-normal" placeholder="Например: replica, копия, тест">
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
                        <?php $__empty_1 = true; $__currentLoopData = $words; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $word): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="p-3">
                                    <form id="update-word-<?php echo e($word->id); ?>" method="POST" action="<?php echo e(route('admin.excluded-words.update', $word)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input name="word" value="<?php echo e($word->word); ?>" maxlength="255" required class="w-full min-w-48 rounded border border-slate-300 p-2">
                                    </form>
                                </td>
                                <td class="p-3">
                                    <select form="update-word-<?php echo e($word->id); ?>" name="active" class="rounded border border-slate-300 p-2">
                                        <option value="1" <?php if($word->active): echo 'selected'; endif; ?>>Активно</option>
                                        <option value="0" <?php if(! $word->active): echo 'selected'; endif; ?>>Неактивно</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <button form="update-word-<?php echo e($word->id); ?>" class="text-xs font-medium text-blue-700 hover:underline">Сохранить</button>
                                        <form method="POST" action="<?php echo e(route('admin.excluded-words.destroy', $word)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="text-xs font-medium text-red-700 hover:underline">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-500">Список исключённых слов пока пуст.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4"><?php echo e($words->links()); ?></div>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/excluded-words/index.blade.php ENDPATH**/ ?>