<dialog
    id="listing-image-gallery-dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="listing-image-gallery-title"
    class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(64rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
    data-listing-image-gallery-dialog
>
    <div class="p-4 sm:p-6">
        <header class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 id="listing-image-gallery-title" class="text-lg font-semibold">Фотографии товара</h2>
                <p class="mt-1 truncate text-sm text-slate-500" data-gallery-item-title></p>
            </div>
            <button type="button" aria-label="Закрыть фотографии" class="rounded p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" data-gallery-close>Закрыть</button>
        </header>
        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" aria-label="Предыдущее фото" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" data-gallery-previous>←</button>
            <p class="text-sm text-slate-600" aria-live="polite" data-gallery-counter></p>
            <button type="button" aria-label="Следующее фото" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" data-gallery-next>→</button>
        </div>
        <div class="mt-3 max-h-[65vh] overflow-auto rounded-lg bg-slate-100 text-center" data-gallery-viewport tabindex="0" aria-label="Фотографии товара; используйте стрелки для прокрутки" style="overflow-x: auto; overflow-y: hidden;">
            <div class="flex w-full snap-x snap-mandatory" data-gallery-track></div>
        </div>
        <p class="mt-2 text-center text-xs text-slate-500">Увеличьте фото, затем зажмите левую кнопку мыши и перетаскивайте изображение.</p>
        <div class="mt-4 flex items-center justify-center gap-2">
            <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" aria-label="Уменьшить фото" data-gallery-zoom-out>−</button>
            <span class="min-w-16 text-center text-sm text-slate-600" data-gallery-zoom-level>100%</span>
            <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" aria-label="Увеличить фото" data-gallery-zoom-in>+</button>
            <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" data-gallery-zoom-reset>Сбросить масштаб</button>
        </div>
    </div>
</dialog>
