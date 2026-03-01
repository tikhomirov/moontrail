@php
    /** @var array<int, \MoonShine\MoonTrail\Models\ModelVersion> $versions */
    /** @var string $label */
    /** @var bool $showDiff */
    /** @var bool $showRollback */
    /** @var bool $canRollback */
    /** @var bool $showRollbackHint */
    /** @var int|null $minVersionId */
    /** @var array<int, int> $changesCount */

    $dateFormat = config('moontrail.ui.date_format', 'd.m.Y H:i:s');

    $eventConfig = [
        'created' => [
            'dot'      => 'bg-green-500 dark:bg-green-600',
            'ring'     => 'ring-green-100 dark:ring-green-900/50',
            'badge'    => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::created(),
            'ms_dot'   => 'ms-al-tl-dot-created',
            'ms_badge' => 'ms-al-event-created',
        ],
        'updated' => [
            'dot'      => 'bg-blue-500 dark:bg-blue-600',
            'ring'     => 'ring-blue-100 dark:ring-blue-900/50',
            'badge'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::updated(),
            'ms_dot'   => 'ms-al-tl-dot-updated',
            'ms_badge' => 'ms-al-event-updated',
        ],
        'deleted' => [
            'dot'      => 'bg-red-500 dark:bg-red-600',
            'ring'     => 'ring-red-100 dark:ring-red-900/50',
            'badge'    => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::deleted(),
            'ms_dot'   => 'ms-al-tl-dot-deleted',
            'ms_badge' => 'ms-al-event-deleted',
        ],
        'restored' => [
            'dot'      => 'bg-purple-500 dark:bg-purple-600',
            'ring'     => 'ring-purple-100 dark:ring-purple-900/50',
            'badge'    => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
            'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::restored(),
            'ms_dot'   => 'ms-al-tl-dot-restored',
            'ms_badge' => 'ms-al-event-restored',
        ],
        'rolled_back' => [
            'dot'      => 'bg-orange-500 dark:bg-orange-600',
            'ring'     => 'ring-orange-100 dark:ring-orange-900/50',
            'badge'    => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
            'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::rolledBack(),
            'ms_dot'   => 'ms-al-tl-dot-rolled-back',
            'ms_badge' => 'ms-al-event-rolled-back',
        ],
    ];

    $defaultConfig = [
        'dot'      => 'bg-gray-400 dark:bg-gray-600',
        'ring'     => 'ring-gray-100 dark:ring-gray-800',
        'badge'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'icon'     => \MoonShine\MoonTrail\Support\SvgIcons::unknown(),
        'ms_dot'   => 'ms-al-tl-dot-unknown',
        'ms_badge' => 'ms-al-event-unknown',
    ];
@endphp

<div class="ms-activity-log-timeline">

    @if($label)
        <h3 id="ms-al-tl-label" class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-4 sr-only">{{ $label }}</h3>
    @endif

    @if(count($versions) === 0)
        <div class="ms-al-empty flex flex-col items-center justify-center py-12 gap-3" role="status" aria-live="polite">
            <svg class="w-14 h-14 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                {{ __('moontrail::ui.no_history') }}
            </span>
        </div>
    @else
        <div class="relative" role="list" @if($label) aria-labelledby="ms-al-tl-label" @endif>
            {{-- Vertical connector line --}}
            @if(count($versions) > 1)
                <div class="absolute left-[17px] top-9 bottom-8 w-0.5 bg-gradient-to-b from-gray-200 via-gray-200 to-transparent dark:from-gray-700 dark:via-gray-700" aria-hidden="true"></div>
            @endif

            @foreach($versions as $version)
                @php
                    $cfg             = $eventConfig[$version->event] ?? $defaultConfig;
                    $eventEnum       = \MoonShine\MoonTrail\Enums\ActivityEvent::tryFrom($version->event);
                    $eventLabel      = $eventEnum?->label() ?? ucfirst(str_replace('_', ' ', $version->event));
                    $authorModel     = $version->author;
                    $authorName      = is_object($authorModel)
                        ? (data_get($authorModel, 'name') ?? data_get($authorModel, 'email'))
                        : null;
                    $authorName      = is_scalar($authorName) ? (string) $authorName : null;
                    $isLatest        = $loop->first;
                    $msDot           = ($cfg['ms_dot'] ?? 'ms-al-tl-dot-unknown') . ($isLatest ? ' ms-al-tl-dot-latest' : '');
                    $msBadge         = $cfg['ms_badge'] ?? 'ms-al-event-unknown';
                    $versionId       = $version->id;
                    $fieldCount      = $changesCount[$versionId] ?? null;
                    $diffPanelId     = 'ms-al-diff-panel-' . $version->id;
                @endphp

                <div class="ms-al-tl-item relative flex gap-4 @if(! $loop->last) pb-5 @endif"
                     role="listitem"
                     x-data="{{ $showDiff && $version->activity_id ? 'Object.assign({ showDiff: false }, activityLogDiff(' . $version->activity_id . '))' : '{ showDiff: false }' }}">

                    {{-- Timeline dot column --}}
                    <div class="ms-al-tl-dot flex-shrink-0 z-10 mt-0.5" aria-hidden="true">
                        <div class="ms-al-tl-dot {{ $msDot }} w-9 h-9 rounded-full {{ $cfg['dot'] }} ring-4 {{ $cfg['ring'] }} flex items-center justify-center text-white shadow-sm
                                    @if($isLatest) shadow-md @endif">
                            {!! $cfg['icon'] !!}
                        </div>
                    </div>

                    {{-- Card --}}
                    <div class="ms-al-tl-card flex-1 min-w-0 overflow-hidden">

                        {{-- Card header --}}
                        <div class="ms-al-tl-head flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                <span class="font-bold text-sm text-gray-800 dark:text-gray-100 whitespace-nowrap">
                                    {{ __('moontrail::ui.version') }} #{{ $version->version }}
                                </span>
                                @if($isLatest)
                                    <span class="ms-al-current-badge inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800 uppercase tracking-wide whitespace-nowrap"
                                          aria-label="{{ __('moontrail::ui.current') }}">
                                        {{ __('moontrail::ui.current') }}
                                    </span>
                                @endif
                                <span class="ms-al-event-badge {{ $msBadge }} inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full {{ $cfg['badge'] }} whitespace-nowrap"
                                      aria-label="{{ $eventLabel }}">
                                    <span aria-hidden="true">{!! $cfg['icon'] !!}</span>
                                    {{ $eventLabel }}
                                </span>
                                @if($version->is_rollback)
                                    <span class="ms-al-event-badge ms-al-event-rolled-back inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 whitespace-nowrap">
                                        <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::rolledBack('w-3 h-3') !!}</span>
                                        → v{{ $version->rollback_to_version }}
                                    </span>
                                @endif
                                @if($fieldCount !== null && $fieldCount > 0)
                                    <span class="ms-al-mini-summary inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700/60 dark:text-gray-300 whitespace-nowrap tabular-nums"
                                          aria-label="{{ $fieldCount }} {{ __('moontrail::ui.diff_fields') }}">
                                        {{ $fieldCount }}&thinsp;Δ
                                    </span>
                                @endif
                            </div>
                            <time class="ms-al-time text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap flex-shrink-0 tabular-nums"
                                  datetime="{{ $version->created_at?->toIso8601String() }}">
                                {{ $version->created_at?->format($dateFormat) }}
                            </time>
                        </div>

                        {{-- Card body --}}
                        <div class="ms-al-tl-body px-4 py-3">
                            {{-- Author --}}
                            @if($authorName)
                                <div class="ms-al-author flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 mb-3">
                                    <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::user('w-3.5 h-3.5', 'flex-shrink-0') !!}</span>
                                    {{ __('moontrail::ui.author') }}:
                                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $authorName }}</span>
                                </div>
                            @endif

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-2 flex-wrap" role="group" aria-label="{{ __('moontrail::ui.version') }} #{{ $version->version }}">
                                @if($showDiff && $version->activity_id)
                                    <button type="button"
                                            @click="showDiff = !showDiff"
                                            :aria-expanded="showDiff ? 'true' : 'false'"
                                            aria-controls="{{ $diffPanelId }}"
                                            class="ms-al-btn ms-al-btn-diff inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
                                            :class="showDiff
                                                ? 'border-blue-400 dark:border-blue-600 bg-blue-600 dark:bg-blue-700 text-white hover:bg-blue-700 dark:hover:bg-blue-600'
                                                : 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40 hover:border-blue-400 dark:hover:border-blue-600'">
                                        <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::diff('w-3.5 h-3.5', 'flex-shrink-0') !!}</span>
                                        <span x-text="showDiff ? @js(__('moontrail::ui.hide_diff')) : @js(__('moontrail::ui.show_diff'))"></span>
                                    </button>
                                    <span x-show="isLoaded && !showDiff"
                                          x-cloak
                                          role="status"
                                          aria-live="polite"
                                          class="ms-al-loaded-badge inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::check('w-3 h-3', 'flex-shrink-0') !!}</span>
                                        {{ __('moontrail::ui.diff_loaded') }}
                                    </span>
                                @endif

                                @if($showRollback && $canRollback && $version->id !== $minVersionId)
                                    <button type="button"
                                            @click="$dispatch('open-rollback-modal', { versionId: {{ $version->id }}, versionNum: {{ $version->version }}, versionAt: @js($version->created_at?->format($dateFormat)) })"
                                            aria-label="{{ __('moontrail::ui.rollback_to_version', ['version' => $version->version]) }}"
                                            class="ms-al-btn ms-al-btn-rollback inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 hover:bg-orange-100 dark:hover:bg-orange-900/40 hover:border-orange-400 dark:hover:border-orange-600 transition-all duration-150 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-1">
                                        <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::rolledBack('w-3.5 h-3.5') !!}</span>
                                        {{ __('moontrail::ui.rollback_to_version', ['version' => $version->version]) }}
                                    </button>
                                @elseif($showRollbackHint && $version->id !== $minVersionId)
                                    <span class="ms-al-rollback-denied inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 text-gray-400 dark:text-gray-500 cursor-not-allowed select-none"
                                          title="{{ __('moontrail::ui.rollback_denied_hint') }}"
                                          aria-label="{{ __('moontrail::ui.rollback_denied_hint') }}">
                                        <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::rolledBack('w-3.5 h-3.5') !!}</span>
                                        {{ __('moontrail::ui.rollback_no_rights') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Inline diff panel --}}
                            @if($showDiff && $version->activity_id)
                                <div id="{{ $diffPanelId }}"
                                     x-show="showDiff"
                                     x-cloak
                                     x-collapse
                                     role="region"
                                     :aria-label="@js(__('moontrail::ui.show_diff'))"
                                     aria-live="polite"
                                     class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/70"
                                     x-effect="if (showDiff) loadOnce()">

                                    {{-- Loading spinner --}}
                                    <template x-if="loading">
                                        <div class="ms-al-loading flex items-center justify-center gap-2 py-6 text-gray-600 dark:text-gray-300" role="status" aria-live="polite">
                                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                            </svg>
                                            <span class="text-sm">{{ __('moontrail::ui.loading') }}…</span>
                                        </div>
                                    </template>

                                    {{-- Diff HTML --}}
                                    <template x-if="!loading && html">
                                        <div x-html="html"></div>
                                    </template>

                                    {{-- Error state --}}
                                    <template x-if="!loading && error">
                                        <div class="ms-al-error flex flex-wrap items-center gap-2 py-3 px-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800" role="alert">
                                            <span aria-hidden="true">{!! \MoonShine\MoonTrail\Support\SvgIcons::error('w-4 h-4', 'text-red-500 flex-shrink-0') !!}</span>
                                            <span class="text-sm text-red-600 dark:text-red-400" x-text="error"></span>
                                            <button type="button"
                                                    @click="retry()"
                                                    class="ms-al-btn-retry inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1">
                                                {{ __('moontrail::ui.retry') }}
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Rollback confirmation modal --}}
    @if($showRollback && $canRollback)
        @include('moontrail::components.rollback-confirm')
    @endif


</div>

@once
<link rel="stylesheet" href="{{ asset('vendor/moontrail/moontrail.css') }}">
<script>
    function activityLogDiff(activityId) {
        return {
            isLoaded: false,
            hasAttempted: false,
            loading: false,
            html: null,
            error: null,
            async loadOnce() {
                if (this.isLoaded || this.hasAttempted || this.loading) return;

                this.hasAttempted = true;
                this.loading = true;
                this.error = null;

                try {
                    const res = await fetch(
                        `{{ url(config('moonshine.prefix', 'admin') . '/moontrail') }}/${activityId}/diff`,
                        { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                    );
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    this.html = data.html;
                    this.isLoaded = true;
                } catch (e) {
                    this.error = `{{ __('moontrail::ui.diff_load_failed') }}`;
                } finally {
                    this.loading = false;
                }
            },
            async retry() {
                if (this.loading || this.isLoaded) return;

                this.hasAttempted = false;
                await this.loadOnce();
            },
        };
    }
</script>
@endonce
