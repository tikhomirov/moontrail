<div class="space-y-6">
    {{-- KPI Cards --}}
    @if(!empty($kpiCards))
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach($kpiCards as $card)
                @php
                    $isActive = $card['isActive'];
                @endphp
                <button
                    type="button"
                    @if($card['event'] !== null)
                        wire:click="filterByEvent('{{ $card['event'] }}')"
                    @else
                        wire:click="$set('event', '')"
                    @endif
                    class="text-left flex items-center gap-3 px-4 py-3 rounded-xl border transition-all duration-150 {{ $isActive ? 'bg-indigo-50/80 dark:bg-indigo-950/40 border-indigo-400 dark:border-indigo-600 ring-2 ring-indigo-500/30' : 'bg-white dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 shadow-sm' }}"
                >
                    <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300">
                        {!! $card['icon'] !!}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-bold text-slate-900 dark:text-slate-100 leading-tight">{{ $card['value'] }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $card['label'] }}</div>
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 shadow-sm space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    {{ __('moontrail::ui.search') }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="{{ __('moontrail::ui.search_hint') }}"
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
            </div>

            {{-- Event --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    {{ __('moontrail::ui.field_event') }}
                </label>
                <select
                    wire:model.live="event"
                    class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">{{ __('moontrail::ui.all_events') }}</option>
                    @foreach($eventOptions as $optVal => $optLabel)
                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Log Name --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    {{ __('moontrail::ui.field_log') }}
                </label>
                <select
                    wire:model.live="logName"
                    class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">{{ __('moontrail::ui.all_logs') }}</option>
                    @foreach($logNameOptions as $optVal => $optLabel)
                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subject Type --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    {{ __('moontrail::ui.field_subject') }}
                </label>
                <select
                    wire:model.live="subjectType"
                    class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">{{ __('moontrail::ui.all_subjects') }}</option>
                    @foreach($subjectTypeOptions as $optVal => $optLabel)
                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                    {{ __('moontrail::ui.filter_date_from') }}
                </label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>
        </div>

        {{-- Active Filter Chips --}}
        @php
            $activeChips = array_filter([
                'search'       => $search !== '' ? __('moontrail::ui.search') . ': "' . $search . '"' : null,
                'event'        => $event !== '' ? __('moontrail::ui.field_event') . ': ' . ($eventOptions[$event] ?? $event) : null,
                'logName'      => $logName !== '' ? __('moontrail::ui.field_log') . ': ' . ($logNameOptions[$logName] ?? $logName) : null,
                'subjectType'  => $subjectType !== '' ? __('moontrail::ui.field_subject') . ': ' . ($subjectTypeOptions[$subjectType] ?? class_basename($subjectType)) : null,
                'dateFrom'     => $dateFrom !== '' ? __('moontrail::ui.filter_date_from') . ': ' . $dateFrom : null,
                'dateUntil'    => $dateUntil !== '' ? __('moontrail::ui.filter_date_until') . ': ' . $dateUntil : null,
            ]);
        @endphp

        @if(!empty($activeChips))
            <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mr-1">
                        {{ __('moontrail::ui.active_filters') }}:
                    </span>
                    @foreach($activeChips as $key => $chipLabel)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            {{ $chipLabel }}
                            <button
                                type="button"
                                wire:click="removeFilter('{{ $key }}')"
                                class="text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-200"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endforeach
                </div>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 underline font-medium"
                >
                    {{ __('moontrail::ui.reset_all_filters') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Main Activity Table --}}
    <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-700 dark:text-slate-200 text-[11px] uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_date') }}</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_event') }}</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_subject') }}</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_causer') }}</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_log') }}</th>
                        <th class="py-3 px-4">{{ __('moontrail::ui.field_description') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('moontrail::ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($activities as $activity)
                        @php
                            $subjectType = $activity->subject_type ?? null;
                            $subjectId = $activity->subject_id ?? null;
                            $subjectName = $activity->subject?->name ?? ($subjectType ? class_basename($subjectType) . ' #' . $subjectId : '—');
                            $hasSubject = $subjectType && $subjectId;
                        @endphp
                        <tr wire:key="act-row-{{ $activity->id }}" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-slate-100">
                                #{{ $activity->id }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $activity->created_at?->format($dateFormat) ?? '—' }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @include('moontrail::components.event-badge', ['activity' => $activity])
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-900 dark:text-slate-100">
                                    {{ $subjectName }}
                                </div>
                                @if($subjectType)
                                    <div class="text-[10px] text-slate-400 font-mono">
                                        {{ class_basename($subjectType) }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($activity->causer)
                                    <span class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ $activity->causer->name ?? $activity->causer->email ?? ('ID: ' . $activity->causer_id) }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">{{ __('moontrail::ui.system') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if(!empty($activity->log_name))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ $activity->log_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate" title="{{ $activity->description }}">
                                {{ $activity->description ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    {{-- Diff Button --}}
                                    <button
                                        type="button"
                                        wire:click="openDiff({{ $activity->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 transition"
                                        title="{{ __('moontrail::ui.view_diff') }}"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        Diff
                                    </button>

                                    {{-- Detail Button --}}
                                    <button
                                        type="button"
                                        wire:click="openDetail({{ $activity->id }})"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 transition"
                                        title="{{ __('moontrail::ui.view_detail') }}"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    {{-- History Timeline Button (if subject model exists) --}}
                                    @if($hasSubject)
                                        <button
                                            type="button"
                                            wire:click="openTimeline('{{ addslashes((string)$subjectType) }}', '{{ $subjectId }}', '{{ addslashes((string)$subjectName) }}')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 transition"
                                            title="{{ __('moontrail::ui.view_history') }}"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('moontrail::ui.no_records_found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($activities->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

    {{-- Embedded Modals --}}
    <livewire:moontrail-diff-modal />
    <livewire:moontrail-detail-modal />
    <livewire:moontrail-timeline-modal />
</div>
