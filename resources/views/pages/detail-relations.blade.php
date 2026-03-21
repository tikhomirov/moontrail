@php
/** @var array{morphType: ?string, morphId: int|string|null, model: mixed} $causer */
/** @var array{morphType: ?string, morphId: int|string|null, model: mixed} $subject */
@endphp
<div class="ms-al-card">
    @include('moontrail::components.section-header', ['type' => 'relations'])
    <div class="px-4 py-2">
        <div class="ms-al-row border-b">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ __('moontrail::ui.field_causer') }}</span>
            <span class="flex items-center gap-2 text-sm flex-wrap">
                @include('moontrail::components.relation-entry', [
                    'morphType' => $causer['morphType'],
                    'morphId' => $causer['morphId'],
                    'model' => $causer['model'],
                    'openLabel' => (string) __('moontrail::ui.open'),
                ])
            </span>
        </div>
        <div class="flex items-center gap-4 py-2.5">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ __('moontrail::ui.field_subject') }}</span>
            <span class="flex items-center gap-2 text-sm flex-wrap">
                @include('moontrail::components.relation-entry', [
                    'morphType' => $subject['morphType'],
                    'morphId' => $subject['morphId'],
                    'model' => $subject['model'],
                    'openLabel' => (string) __('moontrail::ui.open'),
                ])
            </span>
        </div>
    </div>
</div>
