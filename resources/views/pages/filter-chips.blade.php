@php
/** @var array<int, array{label: string, value: string, removeUrl: string}> $chips */
/** @var string $clearAllUrl */
@endphp
<div class="ms-al-filter-chips flex flex-wrap items-center gap-2 mb-3">
    @foreach($chips as $chip)
        <span class="ms-al-filter-chip inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 text-xs font-medium rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
            <span class="opacity-60">{{ $chip['label'] }}:</span>
            <span class="font-semibold">{{ $chip['value'] }}</span>
            <a href="{{ $chip['removeUrl'] }}" class="ms-al-filter-chip-remove inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors duration-100 flex-shrink-0" title="Remove filter">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </span>
    @endforeach
    <a href="{{ $clearAllUrl }}" class="ms-al-filter-clear-all inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-150">
        {{ __('moontrail::ui.filter_clear_all') }}
    </a>
</div>
