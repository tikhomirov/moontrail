<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm"
             x-transition:enter="ease-out duration-200"
             x-transition:leave="ease-in duration-150">
            <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                                {{ __('moontrail::ui.view_detail') }}: #{{ $activityId }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $general['date'] ?? '' }}
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

                {{-- Tabs Navigation --}}
                @if(!$errorMessage)
                    <div class="flex border-b border-slate-200 dark:border-slate-800 px-6 bg-slate-50/40 dark:bg-slate-900/40">
                        <button
                            type="button"
                            wire:click="setTab('general')"
                            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition {{ $activeTab === 'general' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}"
                        >
                            {{ __('moontrail::ui.section_general') }}
                        </button>
                        <button
                            type="button"
                            wire:click="setTab('relations')"
                            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition {{ $activeTab === 'relations' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}"
                        >
                            {{ __('moontrail::ui.section_relations') }}
                        </button>
                        <button
                            type="button"
                            wire:click="setTab('changes')"
                            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition {{ $activeTab === 'changes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}"
                        >
                            {{ __('moontrail::ui.section_changes') }} ({{ count($changes) }})
                        </button>
                    </div>
                @endif

                {{-- Body --}}
                <div class="p-6 overflow-y-auto space-y-4">
                    @if($errorMessage)
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-sm text-red-700 dark:text-red-300">
                            {{ $errorMessage }}
                        </div>
                    @else
                        @if($activeTab === 'general')
                            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden divide-y divide-slate-100 dark:divide-slate-700/50">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-4 py-3">
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('moontrail::ui.field_id') }}</span>
                                    <span class="sm:col-span-2 text-sm font-mono font-bold text-slate-800 dark:text-slate-100">#{{ $general['id'] ?? $activityId }}</span>
                                </div>
                                @if(!empty($general['logName']))
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-4 py-3">
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('moontrail::ui.field_log') }}</span>
                                        <span class="sm:col-span-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                                {{ $general['logName'] }}
                                            </span>
                                        </span>
                                    </div>
                                @endif
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-4 py-3">
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('moontrail::ui.field_date') }}</span>
                                    <span class="sm:col-span-2 text-sm text-slate-800 dark:text-slate-100">{{ $general['date'] ?? '—' }}</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-4 py-3">
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('moontrail::ui.field_description') }}</span>
                                    <span class="sm:col-span-2 text-sm text-slate-800 dark:text-slate-100 leading-relaxed">{{ $general['description'] ?? '—' }}</span>
                                </div>
                            </div>
                        @elseif($activeTab === 'relations')
                            @php
                                $subjectData = $relations['subject'] ?? [];
                                $causerData = $relations['causer'] ?? [];
                                $subjType = $subjectData['morphType'] ?? null;
                                $subjId = $subjectData['morphId'] ?? null;
                                $subjModel = $subjectData['model'] ?? null;
                                $causType = $causerData['morphType'] ?? null;
                                $causId = $causerData['morphId'] ?? null;
                                $causModel = $causerData['model'] ?? null;
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Subject --}}
                                <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-2">
                                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                        {{ __('moontrail::ui.field_subject') }}
                                    </h4>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $subjType ? class_basename($subjType) : '—' }}
                                        @if($subjId)
                                            <span class="font-mono text-xs text-slate-500 dark:text-slate-400">#{{ $subjId }}</span>
                                        @endif
                                    </p>
                                    @if(is_object($subjModel) && isset($subjModel->name))
                                        <p class="text-xs text-slate-600 dark:text-slate-300">
                                            {{ $subjModel->name }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Causer --}}
                                <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 space-y-2">
                                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                        {{ __('moontrail::ui.field_causer') }}
                                    </h4>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $causType ? class_basename($causType) : __('moontrail::ui.system') }}
                                        @if($causId)
                                            <span class="font-mono text-xs text-slate-500 dark:text-slate-400">#{{ $causId }}</span>
                                        @endif
                                    </p>
                                    @if(is_object($causModel) && isset($causModel->name))
                                        <p class="text-xs text-slate-600 dark:text-slate-300">
                                            {{ $causModel->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @elseif($activeTab === 'changes')
                            @include('moontrail::components.diff-viewer', [
                                'changes' => $changes,
                                'compact' => false,
                            ])
                        @endif
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
