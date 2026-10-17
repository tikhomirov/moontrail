<div class="space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-700">
        <div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('moontrail::ui.timeline_title') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ __('moontrail::ui.timeline_total_snapshots', ['count' => $versions->count()]) }}
            </p>
        </div>

        @if($selectedVersionId !== null)
            <button
                type="button"
                wire:click="clearSelection"
                class="inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 font-medium px-2.5 py-1.5 rounded border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ __('moontrail::ui.hide_diff') }}
            </button>
        @endif
    </div>

    @error('rollback')
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-sm text-red-700 dark:text-red-300 flex items-start gap-2.5">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-semibold">{{ __('moontrail::ui.rollback_conflict_header') }}</p>
                <p class="mt-0.5">{{ $message }}</p>
            </div>
        </div>
    @enderror

    @if($versions->isEmpty())
        <div class="text-center py-12 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700">
            <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('moontrail::ui.timeline_no_snapshots') }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('moontrail::ui.timeline_no_snapshots_hint') }}</p>
        </div>
    @else
        <div class="relative pl-6 space-y-6 before:absolute before:top-3 before:bottom-3 before:left-2.5 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
            @foreach ($versions as $version)
                @php
                    $isSelected = ($selectedVersionId === $version->id);
                    $isLatest = $loop->first;
                @endphp
                <div wire:key="version-{{ $version->id }}" class="relative group">
                    {{-- Dot on vertical line --}}
                    <div class="absolute -left-6 top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-slate-900 {{ $isSelected ? 'bg-indigo-600 ring-4 ring-indigo-100 dark:ring-indigo-950/60' : ($isLatest ? 'bg-emerald-500' : 'bg-slate-400') }}"></div>

                    {{-- Version Card --}}
                    <div class="rounded-lg border transition-all duration-150 {{ $isSelected ? 'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm' : 'border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600' }} p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $isLatest ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                    #{{ $version->version }}
                                </span>
                                @if($isLatest)
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        {{ __('moontrail::ui.status') }}: Текущая
                                    </span>
                                @endif
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $version->created_at?->format('d.m.Y H:i:s') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($version->author)
                                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $version->author->name ?? ('ID: ' . $version->author_id) }}
                                    </span>
                                @endif

                                <button
                                    type="button"
                                    wire:click="selectVersion({{ $version->id }})"
                                    class="text-xs font-medium px-2.5 py-1 rounded border transition {{ $isSelected ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700' : 'text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                                >
                                    {{ $isSelected ? __('moontrail::ui.hide_diff') : __('moontrail::ui.show_diff') }}
                                </button>
                            </div>
                        </div>

                        {{-- Expanded Diff view --}}
                        @if($isSelected && $selectedVersion !== null && !empty($changes))
                            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2 uppercase tracking-wide">
                                    {{ __('moontrail::ui.compare_with_current') }}:
                                </p>
                                @include('moontrail::components.diff-viewer', [
                                    'changes' => $changes,
                                    'compact' => true,
                                ])
                            </div>
                        @endif

                        {{-- Rollback Button (only for earlier versions) --}}
                        @if($isSelected && $version->version < $versions->first()->version)
                            <div class="mt-4 pt-3 border-t border-indigo-100 dark:border-indigo-900/50 flex items-center justify-between">
                                <span class="text-xs text-indigo-700 dark:text-indigo-300">
                                    {{ __('moontrail::ui.rollback_warning_note') }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="promptRollback"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition"
                                >
                                    {{ __('moontrail::ui.rollback_to_version', ['version' => $version->version]) }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Rollback Confirmation Modal --}}
    @if($confirmingRollback && $selectedVersion !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-transition:enter="ease-out duration-200"
             x-transition:leave="ease-in duration-150">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('moontrail::ui.rollback_confirm_title') }} (#{{ $selectedVersion->version }})
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $selectedVersion->created_at?->format('d.m.Y H:i:s') }}
                        </p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('moontrail::ui.rollback_confirm_snapshot_note') }}
                </p>

                <div class="p-3 rounded bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-300">
                    {{ __('moontrail::ui.rollback_warning') }}
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <button
                        type="button"
                        wire:click="cancelRollback"
                        class="px-3 py-1.5 rounded text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        {{ __('moontrail::ui.cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="rollback"
                        class="px-3 py-1.5 rounded text-xs font-semibold bg-red-600 hover:bg-red-700 text-white shadow-sm transition"
                    >
                        {{ __('moontrail::ui.rollback_confirm_button') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
