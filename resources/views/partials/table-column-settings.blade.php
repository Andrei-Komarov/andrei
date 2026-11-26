<div class="relative flex justify-end {{ $containerClass ?? 'px-3 py-2' }}" data-column-settings data-table-key="{{ $tableKey }}">
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        aria-label="Настроить столбцы таблицы"
        aria-expanded="false"
        aria-controls="column-settings-{{ $tableKey }}"
        data-column-settings-toggle
    >
        <svg class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
        </svg>
        Столбцы
    </button>
    <div
        id="column-settings-{{ $tableKey }}"
        class="absolute right-3 top-full z-30 hidden max-h-96 w-72 max-w-[calc(100vw-2rem)] overflow-y-auto rounded-lg border border-slate-200 bg-white p-4 text-slate-900 shadow-xl"
        role="dialog"
        aria-label="Настройка столбцов таблицы"
        data-column-settings-panel
    >
        <h2 class="font-semibold">Показывать столбцы</h2>
        <div class="mt-3 space-y-2" data-column-settings-options></div>
        <p class="mt-3 hidden text-xs text-red-700" role="status" aria-live="polite" data-column-settings-error></p>
    </div>
</div>
