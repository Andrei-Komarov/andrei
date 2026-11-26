@php
    $currentSort = request()->query('sort', $sort ?? $defaultSort ?? '');
    $ascendingSort = $sortKey.'_asc';
    $descendingSort = $sortKey.'_desc';
    $isSorted = in_array($currentSort, [$ascendingSort, $descendingSort], true);
    $nextSort = $currentSort === $ascendingSort ? $descendingSort : $ascendingSort;
    $routeParameters = array_merge(request()->route()->parameters(), request()->query());
    unset($routeParameters['page']);
    $routeParameters['sort'] = $nextSort;
    $sortDirection = $currentSort === $ascendingSort ? 'ascending' : 'descending';
@endphp
<th class="{{ $class ?? 'p-3' }}" data-column-key="{{ $columnKey }}" data-column-label="{{ $label }}" @if ($isSorted) aria-sort="{{ $sortDirection }}" @endif>
    <a href="{{ route(request()->route()->getName(), $routeParameters) }}" class="hover:text-blue-700">
        {{ $label }} {{ $isSorted ? ($currentSort === $ascendingSort ? '↑' : '↓') : '↕' }}
        <span class="sr-only">{{ $isSorted ? ($currentSort === $ascendingSort ? 'Сортировка по возрастанию' : 'Сортировка по убыванию') : 'Сортировать по возрастанию' }}</span>
    </a>
</th>
