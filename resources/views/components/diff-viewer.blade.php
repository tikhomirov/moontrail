@php
    /** @var array<string, \MoonShine\MoonTrail\Diff\FieldChange> $changes */
    /** @var bool $compact */

    $rowConfig = [
        'added'     => [
            'border'      => 'border-l-emerald-500',
            'oldColor'    => 'text-gray-600 dark:text-gray-400 italic',
            'newColor'    => 'text-emerald-700 dark:text-emerald-400 font-semibold',
            'status_bg'   => 'bg-emerald-50 dark:bg-emerald-950/60',
            'status_text' => 'text-emerald-700 dark:text-emerald-400',
            'dot'         => 'bg-emerald-500',
            'ms_row'      => 'ms-al-diff-added',
            'ms_status'   => 'ms-al-status-added',
        ],
        'removed'   => [
            'border'      => 'border-l-red-500',
            'oldColor'    => 'text-red-600 dark:text-red-400 line-through decoration-2',
            'newColor'    => 'text-gray-600 dark:text-gray-400 italic',
            'status_bg'   => 'bg-red-50 dark:bg-red-950/60',
            'status_text' => 'text-red-700 dark:text-red-400',
            'dot'         => 'bg-red-500',
            'ms_row'      => 'ms-al-diff-removed',
            'ms_status'   => 'ms-al-status-removed',
        ],
        'modified'  => [
            'border'      => 'border-l-blue-500',
            'oldColor'    => 'text-red-600 dark:text-red-400',
            'newColor'    => 'text-emerald-700 dark:text-emerald-400 font-semibold',
            'status_bg'   => 'bg-blue-50 dark:bg-blue-950/60',
            'status_text' => 'text-blue-700 dark:text-blue-400',
            'dot'         => 'bg-blue-500',
            'ms_row'      => 'ms-al-diff-modified',
            'ms_status'   => 'ms-al-status-modified',
        ],
        'unchanged' => [
            'border'      => 'border-l-transparent',
            'oldColor'    => 'text-gray-700 dark:text-gray-300',
            'newColor'    => 'text-gray-700 dark:text-gray-300',
            'status_bg'   => 'bg-gray-50 dark:bg-gray-800/80',
            'status_text' => 'text-gray-700 dark:text-gray-300',
            'dot'         => 'bg-gray-400 dark:bg-gray-600',
            'ms_row'      => 'ms-al-diff-unchanged',
            'ms_status'   => 'ms-al-status-unchanged',
        ],
    ];

    $labelMap = [
        'added'     => __('moontrail::ui.change_added'),
        'removed'   => __('moontrail::ui.change_removed'),
        'modified'  => __('moontrail::ui.change_modified'),
        'unchanged' => __('moontrail::ui.change_unchanged'),
    ];
@endphp

@php
    $totalCount   = count($changes);
    $changedCount = collect($changes)->filter(fn ($c) => $c->type->value !== 'unchanged')->count();
    $hasUnchanged = $changedCount < $totalCount;
@endphp

@if($totalCount === 0)
    <div class="ms-al-empty flex flex-col items-center justify-center py-12 gap-3">
        <svg class="w-14 h-14 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
            {{ __('moontrail::ui.no_changes') }}
        </span>
    </div>
@else
    <div x-data="{ onlyChanged: {{ $hasUnchanged && ($changedCount > 0) ? 'true' : 'false' }} }">

    {{-- Summary bar --}}
    <div class="ms-al-diff-summary flex items-center justify-between gap-3 px-1 pb-2">
        <span class="ms-al-diff-count text-xs text-gray-500 dark:text-gray-400" role="status" aria-live="polite">
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $changedCount }}</span>
            {{ __('moontrail::ui.diff_changed_of') }}
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $totalCount }}</span>
            {{ __('moontrail::ui.diff_fields') }}
        </span>
        @if($hasUnchanged)
            <button type="button"
                    @click="onlyChanged = !onlyChanged"
                    :aria-pressed="onlyChanged ? 'true' : 'false'"
                    :aria-label="onlyChanged ? @js(__('moontrail::ui.diff_show_all')) : @js(__('moontrail::ui.diff_only_changed'))"
                    class="ms-al-diff-toggle inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg border transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
                    :class="onlyChanged
                        ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'">
                <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::filter('w-3 h-3', 'flex-shrink-0') !!}</span>
                <span x-text="onlyChanged ? @js(__('moontrail::ui.diff_show_all')) : @js(__('moontrail::ui.diff_only_changed'))"></span>
            </button>
        @endif
    </div>

    <div class="ms-al-diff-table-wrap">
        <table class="ms-activity-log-diff w-full text-sm border-collapse" role="table" aria-label="{{ __('moontrail::ui.field') }}">
            <thead>
                <tr class="ms-al-diff-thead-row">
                    <th scope="col" class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider w-36 border-l-4 border-l-transparent">
                        {{ __('moontrail::ui.field') }}
                    </th>
                    <th scope="col" class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-400 dark:bg-red-500 flex-shrink-0" aria-hidden="true"></span>
                            {{ __('moontrail::ui.before') }}
                        </span>
                    </th>
                    <th scope="col" class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 dark:bg-emerald-500 flex-shrink-0" aria-hidden="true"></span>
                            {{ __('moontrail::ui.after') }}
                        </span>
                    </th>
                    @if(! ($compact ?? false))
                        <th scope="col" class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider w-32">
                            {{ __('moontrail::ui.status') }}
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="ms-al-diff-tbody">
                @foreach($changes as $change)
                    @php
                        $typeValue  = $change->type->value;
                        $cfg        = $rowConfig[$typeValue] ?? $rowConfig['unchanged'];
                        $label      = $labelMap[$typeValue] ?? $typeValue;
                        $isMasked   = $change->masked;
                        $oldIsJson  = is_array($change->oldValue)
                            || (is_object($change->oldValue) && ! is_scalar($change->oldValue));
                        $newIsJson  = is_array($change->newValue)
                            || (is_object($change->newValue) && ! is_scalar($change->newValue));
                        $oldIsJson  = $isMasked ? false : $oldIsJson;
                        $newIsJson  = $isMasked ? false : $newIsJson;
                        $oldJsonStr = $oldIsJson
                            ? json_encode($change->oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                            : null;
                        $newJsonStr = $newIsJson
                            ? json_encode($change->newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                            : null;
                        $oldDisplay = $isMasked
                            ? '********'
                            : ($oldIsJson
                            ? Str::limit(json_encode($change->oldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 60, '…')
                            : ($change->oldValue ?? '—'));
                        $newDisplay = $isMasked
                            ? '********'
                            : ($newIsJson
                            ? Str::limit(json_encode($change->newValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 60, '…')
                            : ($change->newValue ?? '—'));
                        $msRow    = $cfg['ms_row']    ?? 'ms-al-diff-unchanged';
                        $msStatus = $cfg['ms_status'] ?? 'ms-al-status-unchanged';
                    @endphp
                    <tr class="ms-al-diff-row {{ $msRow }} border-l-4 {{ $cfg['border'] }} hover:bg-gray-50/60 dark:hover:bg-white/[0.02] transition-colors duration-150"
                        @if($typeValue === 'unchanged') x-show="!onlyChanged" @endif>

                        {{-- Field name --}}
                        <td class="py-3 px-4 font-mono text-xs font-bold text-gray-700 dark:text-gray-200 whitespace-nowrap align-top">
                            {{ $change->field }}
                        </td>

                        {{-- Before value --}}
                        <td class="py-3 px-4 max-w-xs align-top">
                            @if($oldIsJson)
                                <div x-data="activityLogJsonCell({{ Js::from($oldJsonStr) }})" class="space-y-1.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <code class="ms-al-diff-old font-mono text-xs {{ $cfg['oldColor'] }} truncate max-w-[13rem]">{{ $oldDisplay }}</code>
                                        {{-- Expand button --}}
                                        <button type="button" @click="toggle()"
                                                :aria-expanded="open ? 'true' : 'false'"
                                                :aria-label="open ? '{{ __('moontrail::ui.collapse') }}' : '{{ __('moontrail::ui.expand') }}'"
                                                class="ms-al-btn-expand inline-flex items-center gap-1 px-1.5 py-0.5 text-xs rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1">
                                            <svg class="w-3 h-3 transition-transform duration-200 flex-shrink-0"
                                                 :class="open ? 'rotate-180' : ''"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                            <span x-text="open ? '{{ __('moontrail::ui.collapse') }}' : '{{ __('moontrail::ui.expand') }}'"></span>
                                        </button>
                                        {{-- Copy button --}}
                                        <button type="button" @click="copyJson()"
                                                :aria-label="copied ? '{{ __('moontrail::ui.copied') }}' : '{{ __('moontrail::ui.copy') }}'"
                                                class="ms-al-btn-copy inline-flex items-center gap-1 px-1.5 py-0.5 text-xs rounded-md border transition-all duration-200 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
                                                :class="copied
                                                    ? 'ms-al-copied border-emerald-400 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'">
                                            <template x-if="!copied">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </template>
                                            <template x-if="copied">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </template>
                                            <span x-text="copied ? '{{ __('moontrail::ui.copied') }}' : '{{ __('moontrail::ui.copy') }}'"></span>
                                        </button>
                                    </div>
                                    <div x-show="open" x-cloak x-collapse>
                                        <pre class="ms-al-json-viewer mt-1.5 p-3 rounded-lg text-xs bg-[#0d1117] overflow-x-auto whitespace-pre font-mono border border-gray-800 dark:border-gray-700 leading-relaxed select-all"><code x-html="open ? highlight(json) : ''"></code></pre>
                                    </div>
                                </div>
                            @else
                                <span class="ms-al-diff-old font-mono text-xs {{ $cfg['oldColor'] }} break-all whitespace-pre-wrap inline-block max-w-full">{{ $change->oldValue ?? '—' }}</span>
                            @endif
                        </td>

                        {{-- After value --}}
                        <td class="py-3 px-4 max-w-xs align-top">
                            @if($newIsJson)
                                <div x-data="activityLogJsonCell({{ Js::from($newJsonStr) }})" class="space-y-1.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <code class="ms-al-diff-new font-mono text-xs {{ $cfg['newColor'] }} truncate max-w-[13rem]">{{ $newDisplay }}</code>
                                        {{-- Expand button --}}
                                        <button type="button" @click="toggle()"
                                                :aria-expanded="open ? 'true' : 'false'"
                                                :aria-label="open ? '{{ __('moontrail::ui.collapse') }}' : '{{ __('moontrail::ui.expand') }}'"
                                                class="ms-al-btn-expand inline-flex items-center gap-1 px-1.5 py-0.5 text-xs rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1">
                                            <svg class="w-3 h-3 transition-transform duration-200 flex-shrink-0"
                                                 :class="open ? 'rotate-180' : ''"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                            <span x-text="open ? '{{ __('moontrail::ui.collapse') }}' : '{{ __('moontrail::ui.expand') }}'"></span>
                                        </button>
                                        {{-- Copy button --}}
                                        <button type="button" @click="copyJson()"
                                                :aria-label="copied ? '{{ __('moontrail::ui.copied') }}' : '{{ __('moontrail::ui.copy') }}'"
                                                class="ms-al-btn-copy inline-flex items-center gap-1 px-1.5 py-0.5 text-xs rounded-md border transition-all duration-200 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
                                                :class="copied
                                                    ? 'ms-al-copied border-emerald-400 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'">
                                            <template x-if="!copied">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </template>
                                            <template x-if="copied">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </template>
                                            <span x-text="copied ? '{{ __('moontrail::ui.copied') }}' : '{{ __('moontrail::ui.copy') }}'"></span>
                                        </button>
                                    </div>
                                    <div x-show="open" x-cloak x-collapse>
                                        <pre class="ms-al-json-viewer mt-1.5 p-3 rounded-lg text-xs bg-[#0d1117] overflow-x-auto whitespace-pre font-mono border border-gray-800 dark:border-gray-700 leading-relaxed select-all"><code x-html="open ? highlight(json) : ''"></code></pre>
                                    </div>
                                </div>
                            @else
                                <span class="ms-al-diff-new font-mono text-xs {{ $cfg['newColor'] }} break-all whitespace-pre-wrap inline-block max-w-full">{{ $change->newValue ?? '—' }}</span>
                            @endif
                        </td>

                        @if(! ($compact ?? false))
                            <td class="py-3 px-4 whitespace-nowrap align-top">
                                <span class="ms-al-status-badge {{ $msStatus }} inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $cfg['status_bg'] }} {{ $cfg['status_text'] }}">
                                    <span class="ms-al-status-dot w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $cfg['dot'] }}"></span>
                                    {{ $label }}
                                </span>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>{{-- /x-data onlyChanged --}}
@endif

@once
<link rel="stylesheet" href="{{ asset('vendor/moontrail/moontrail.css') }}">
<script>
    function activityLogJsonCell(json) {
        return {
            open: false,
            copied: false,
            json: json,
            toggle() {
                this.open = !this.open;
            },
            async copyJson() {
                try {
                    await navigator.clipboard.writeText(this.json);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                } catch (_) {}
            },
            highlight(raw) {
                // Escape HTML entities first, then apply syntax coloring
                const escaped = raw
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                return escaped.replace(
                    /("(?:\\u[0-9a-fA-F]{4}|\\[^u]|[^\\"])*"(?:\s*:)?|\b(?:true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
                    (match) => {
                        let cls = 'color:#f97316'; // number — orange
                        if (/^"/.test(match)) {
                            cls = /:$/.test(match) ? 'color:#79c0ff' : 'color:#a5d6a7'; // key — blue, string — green
                        } else if (/true|false/.test(match)) {
                            cls = 'color:#c792ea'; // boolean — purple
                        } else if (/null/.test(match)) {
                            cls = 'color:#6e7681'; // null — muted
                        }
                        return `<span style="${cls}">${match}</span>`;
                    }
                );
            },
        };
    }
</script>
@endonce
