<?php
    $currentSort = request()->query('sort', $sort ?? $defaultSort ?? '');
    $ascendingSort = $sortKey.'_asc';
    $descendingSort = $sortKey.'_desc';
    $isSorted = in_array($currentSort, [$ascendingSort, $descendingSort], true);
    $nextSort = $currentSort === $ascendingSort ? $descendingSort : $ascendingSort;
    $routeParameters = array_merge(request()->route()->parameters(), request()->query());
    unset($routeParameters['page']);
    $routeParameters['sort'] = $nextSort;
    $sortDirection = $currentSort === $ascendingSort ? 'ascending' : 'descending';
?>
<th class="<?php echo e($class ?? 'p-3'); ?>" data-column-key="<?php echo e($columnKey); ?>" data-column-label="<?php echo e($label); ?>" <?php if($isSorted): ?> aria-sort="<?php echo e($sortDirection); ?>" <?php endif; ?>>
    <a href="<?php echo e(route(request()->route()->getName(), $routeParameters)); ?>" class="hover:text-blue-700">
        <?php echo e($label); ?> <?php echo e($isSorted ? ($currentSort === $ascendingSort ? '↑' : '↓') : '↕'); ?>

        <span class="sr-only"><?php echo e($isSorted ? ($currentSort === $ascendingSort ? 'Сортировка по возрастанию' : 'Сортировка по убыванию') : 'Сортировать по возрастанию'); ?></span>
    </a>
</th>
<?php /**PATH /home/andrei/web/ebay/resources/views/partials/sortable-table-heading.blade.php ENDPATH**/ ?>