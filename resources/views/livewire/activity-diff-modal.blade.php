<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm"
             x-transition:enter="ease-out duration-200"
             x-transition:leave="ease-in duration-150">
            <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                                    {{ __('moontrail::ui.view_diff') }}: {{ $subjectName }}
                                </h3>
                                @if($event)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        #{{ $activityId }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                @if($causer)
                                    <span>{{ __('moontrail::ui.author') }}: <strong>{{ $causer }}</strong></span>
                                    <span>•</span>
                                @endif
                                @if($date)
                                    <span>{{ $date }}</span>
                                @endif
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
                    @if($errorMessage)
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-sm text-red-700 dark:text-red-300">
                            {{ $errorMessage }}
                        </div>
                    @else
                        @include('moontrail::components.diff-viewer', [
                            'changes' => $changes,
                            'compact' => false,
                        ])
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex justify-end">
                    <button
                        type="button"
                        wire:click="close"
                        class="px-4 py-2 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        {{ __('moontrail::ui.modal_close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
