@php
/** @var string $id */
/** @var string|null $logName */
/** @var \Spatie\Activitylog\Models\Activity $activity */
/** @var string $description */
/** @var string $date */
@endphp
<div class="ms-al-card">
    @include('moontrail::components.section-header', ['type' => 'general'])
    <div class="px-4 py-2">
        <div class="ms-al-row flex items-start gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40 last:border-0">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide pt-0.5">{{ __('moontrail::ui.field_id') }}</span>
            <span class="ms-al-row-value text-sm text-gray-800 dark:text-gray-100 leading-relaxed"><span class="font-mono font-bold text-gray-600 dark:text-gray-300">#{{ $id }}</span></span>
        </div>
        @if($logName !== null)
            <div class="ms-al-row flex items-start gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40 last:border-0">
                <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide pt-0.5">{{ __('moontrail::ui.field_log') }}</span>
                <span class="ms-al-row-value text-sm text-gray-800 dark:text-gray-100 leading-relaxed"><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $logName }}</span></span>
            </div>
        @endif
        <div class="flex items-center gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ __('moontrail::ui.field_event') }}</span>
            <span class="text-sm">@include('moontrail::components.event-badge', ['activity' => $activity])</span>
        </div>
        <div class="ms-al-row flex items-start gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40 last:border-0">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide pt-0.5">{{ __('moontrail::ui.field_description') }}</span>
            <span class="ms-al-row-value text-sm text-gray-800 dark:text-gray-100 leading-relaxed">{{ $description }}</span>
        </div>
        <div class="ms-al-row flex items-start gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40 last:border-0">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide pt-0.5">{{ __('moontrail::ui.field_date') }}</span>
            <span class="ms-al-row-value text-sm text-gray-800 dark:text-gray-100 leading-relaxed"><span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $date }}</span></span>
        </div>
    </div>
</div>
