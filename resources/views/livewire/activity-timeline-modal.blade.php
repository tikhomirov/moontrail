<div>
    @if($isOpen && $modelClass !== null && $modelId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm"
             x-transition:enter="ease-out duration-200"
             x-transition:leave="ease-in duration-150">
            <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                                {{ $title ?? ('История изменений: ' . class_basename($modelClass) . ' #' . $modelId) }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('moontrail::ui.timeline_title') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="close"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 overflow-y-auto">
                    <livewire:moontrail-timeline
                        :key="'modal-tl-' . $modelClass . '-' . $modelId"
                        :model-class="$modelClass"
                        :model-id="$modelId"
                    />
                </div>
            </div>
        </div>
    @endif
</div>
