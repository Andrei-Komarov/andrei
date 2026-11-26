<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => ['title' => 'Пользователи и роли']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Пользователи и роли']); ?>
    <div class="min-w-0">
                <?php if(session('success')): ?>
                    <div class="mb-4 rounded bg-emerald-100 p-3 text-emerald-800"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if($errors->any()): ?>
                    <div class="mb-4 rounded bg-red-100 p-3 text-red-800">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <p><?php echo e($error); ?></p>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <section class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Пользователей</p><p class="mt-1 text-2xl font-bold"><?php echo e($userCount); ?></p></div>
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Профилей поиска</p><p class="mt-1 text-2xl font-bold"><?php echo e($profileCount); ?></p></div>
                    <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Записей истории</p><p class="mt-1 text-2xl font-bold"><?php echo e($activityCount); ?></p></div>
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
                    <?php echo $__env->make('partials.table-column-settings', ['tableKey' => 'admin-users'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="overflow-x-auto">
                        <table class="data-table text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'name', 'sortKey' => 'name', 'label' => 'Имя', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'email', 'sortKey' => 'email', 'label' => 'Email', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'registered', 'sortKey' => 'registered', 'label' => 'Регистрация', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo $__env->make('partials.sortable-table-heading', ['columnKey' => 'role', 'sortKey' => 'role', 'label' => 'Роль', 'sort' => $sort], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <th class="p-3" data-column-key="actions">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="p-3 font-medium"><?php echo e($user->name); ?></td>
                                        <td class="p-3"><?php echo e($user->email); ?></td>
                                        <td class="p-3 text-slate-600"><?php echo e($user->created_at->format('d.m.Y H:i')); ?></td>
                                        <td class="p-3">
                                            <form method="POST" action="<?php echo e(route('admin.users.role', $user)); ?>" class="flex flex-wrap items-center gap-2">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <input type="hidden" name="is_admin" value="<?php echo e($user->isAdmin() ? 0 : 1); ?>">
                                                <span class="text-xs <?php echo e($user->isAdmin() ? 'text-blue-700' : 'text-slate-500'); ?>"><?php echo e($user->isAdmin() ? 'Администратор' : 'Пользователь'); ?></span>
                                                <?php if(! $user->isAdmin()): ?>
                                                    <button class="text-xs font-medium text-blue-700 hover:underline">Назначить администратором</button>
                                                <?php elseif(auth('admin')->id() !== $user->id && mb_strtolower($user->email) !== mb_strtolower((string) config('services.admin.email'))): ?>
                                                    <button class="text-xs font-medium text-red-700 hover:underline" onclick="this.form.is_admin.value = 0">Снять роль</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="text-xs font-medium text-blue-700 hover:underline">Редактировать</a>
                                                <?php if(auth('admin')->id() !== $user->id && mb_strtolower($user->email) !== mb_strtolower((string) config('services.admin.email'))): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>"
                                                        data-confirm-dialog-form
                                                        data-confirm-button="Удалить пользователя"
                                                        data-confirm-message="Удалить пользователя <?php echo e($user->name); ?>? Его профили и связанные товары также будут удалены, история действий сохранится.">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button class="text-xs font-medium text-red-700 hover:underline">Удалить</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4"><?php echo e($users->links()); ?></div>
                </section>
    </div>
    <?php echo $__env->make('partials.confirm-dialog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH /home/andrei/web/ebay/resources/views/admin/index.blade.php ENDPATH**/ ?>