<div x-data="{ open: false, versionId: null, versionNum: null, versionAt: null }"
     @open-rollback-modal.window="open = true; versionId = $event.detail.versionId; versionNum = $event.detail.versionNum; versionAt = $event.detail.versionAt ?? null"
     @keydown.escape.window="open = false">

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                 @click="open = false"
                 aria-hidden="true"></div>

            {{-- Dialog card --}}
            <div class="relative z-10 w-full max-w-md rounded-xl bg-white p-6 text-gray-900 shadow-2xl transition-all dark:bg-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-700"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="ms-al-rollback-title"
                 aria-describedby="ms-al-rollback-desc"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                {{-- Header --}}
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="ms-al-rollback-title" class="text-base font-semibold leading-6">
                            {{ __('moontrail::ui.rollback_confirm_title') }}
                        </h3>
                        <p id="ms-al-rollback-desc" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('moontrail::ui.rollback_confirm_text') }}
                            <strong x-text="'#' + versionNum" class="text-orange-600 dark:text-orange-400"></strong>
                        </p>
                        <p x-show="versionAt" x-cloak class="text-xs text-gray-400 dark:text-gray-500 tabular-nums mt-0.5" x-text="versionAt"></p>
                    </div>
                </div>

                {{-- Warning box --}}
                <div class="mb-5 rounded-lg border border-orange-200 bg-orange-50/80 p-3 text-xs leading-relaxed text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300 space-y-1" role="note">
                    <p>{{ __('moontrail::ui.rollback_confirm_snapshot_note') }}</p>
                    <p>{{ __('moontrail::ui.rollback_confirm_overwrite_warning') }}</p>
                    <p>{{ __('moontrail::ui.rollback_confirm_history_warning') }}</p>
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <button type="button"
                            @click="open = false"
                            class="btn btn-secondary inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-1"
                            aria-label="{{ __('moontrail::ui.cancel') }}">
                        {{ __('moontrail::ui.cancel') }}
                    </button>

                    <form method="POST" action="{{ route('moonshine.moontrail.rollback') }}">
                        @csrf
                        <input type="hidden" name="modelVersion" :value="versionId">
                        <button type="submit"
                                class="btn btn-error inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 active:bg-red-800 transition-colors duration-150 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            {{ __('moontrail::ui.rollback_confirm_button') }}
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </template>
</div>
