<dialog
    id="site-confirm-dialog"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="site-confirm-dialog-title"
    aria-describedby="site-confirm-dialog-message"
    class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100%-2rem)] w-[min(28rem,calc(100%-2rem))] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60"
    data-confirm-dialog
>
    <div class="p-6">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A2 2 0 0 0 3.5 21h17a2 2 0 0 0 1.7-2.4L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                </svg>
            </span>
            <div>
                <h2 id="site-confirm-dialog-title" class="text-lg font-semibold">Подтвердите действие</h2>
                <p id="site-confirm-dialog-message" class="mt-2 text-sm leading-6 text-slate-600" data-confirm-dialog-message></p>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" data-confirm-dialog-cancel autofocus>Отмена</button>
            <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" data-confirm-dialog-accept>Подтвердить</button>
        </div>
    </div>
</dialog>
