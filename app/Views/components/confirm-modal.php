<dialog id="bvlog-confirm-dialog"
        closedby="any"
        aria-labelledby="bvlog-confirm-title"
        x-data="bvlogConfirmModal()"
        @open-confirm-modal.window="openModal($event.detail)"
        class="w-full max-w-md rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-0 shadow-2xl">
    <div class="p-6 space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-lg border flex items-center justify-center shrink-0"
                     :class="variant === 'danger'
                        ? 'border-red-200 dark:border-red-900/80 bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400'
                        : 'border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200'">
                    <i class="text-base"
                       :class="variant === 'danger' ? 'fa-solid fa-triangle-exclamation' : (mode === 'prompt' ? 'fa-solid fa-link' : 'fa-solid fa-circle-info')"></i>
                </div>
                <div class="space-y-1 pt-0.5">
                    <h3 id="bvlog-confirm-title" class="text-sm font-bold tracking-tight" x-text="title">İşlem Onayı</h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed" x-text="message"></p>
                </div>
            </div>
            <button type="button"
                    @click="cancel()"
                    class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-1 rounded"
                    aria-label="Kapat">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <template x-if="mode === 'prompt'">
            <div class="pt-1">
                <input type="text"
                       x-ref="promptInput"
                       x-model="promptValue"
                       :placeholder="placeholder"
                       @keydown.enter.prevent="confirm()"
                       class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>
        </template>

        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <template x-if="mode !== 'alert'">
                <button type="button"
                        @click="cancel()"
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                        x-text="cancelText">
                    Vazgeç
                </button>
            </template>
            <button type="button"
                    x-ref="confirmBtn"
                    @click="confirm()"
                    :class="variant === 'danger'
                        ? 'bg-red-600 text-white hover:bg-red-700'
                        : 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 hover:opacity-90'"
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition-colors"
                    x-text="confirmText">
                Onayla
            </button>
        </div>
    </div>
</dialog>
