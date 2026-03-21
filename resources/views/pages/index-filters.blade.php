@php
/** @var array<string, string> $logNameOptions */
/** @var array<string, string> $eventOptions */
/** @var string $currentLogName */
/** @var string $currentEvent */
/** @var string $currentSearch */
/** @var string $currentDateFrom */
/** @var string $currentDateUntil */
@endphp
<div class="ms-al-inline-filters">
    <form method="GET" action="" class="ms-al-filter-grid">
        <!-- Search -->
        <div class="ms-al-filter-search">
            <label>{{ __('moontrail::ui.search') }}</label>
            <div class="ms-al-search-input-wrap">
                <div class="ms-al-search-input-icon" aria-hidden="true">
                    <svg class="ms-al-search-input-icon-svg" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('moontrail::ui.search_hint') }}" class="ms-al-search-input">
            </div>
        </div>

        <!-- Log Name -->
        <div>
            <label>{{ __('moontrail::ui.field_log') }}</label>
            <select name="log_name" onchange="this.form.submit()">
                <option value="">{{ __('moontrail::ui.any') }}</option>
                @foreach($logNameOptions as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected($currentLogName === $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        </div>

        <!-- Event -->
        <div>
            <label>{{ __('moontrail::ui.field_event') }}</label>
            <select name="event" onchange="this.form.submit()">
                <option value="">{{ __('moontrail::ui.any') }}</option>
                @foreach($eventOptions as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected($currentEvent === $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        </div>

        <!-- Date From -->
        <div>
            <label>{{ __('moontrail::ui.date_from') }}</label>
            <input type="date" name="date_from" value="{{ $currentDateFrom }}" onchange="this.form.submit()">
        </div>

        <!-- Date Until -->
        <div>
            <label>{{ __('moontrail::ui.date_until') }}</label>
            <input type="date" name="date_until" value="{{ $currentDateUntil }}" onchange="this.form.submit()">
        </div>

        <div class="ms-al-filter-actions">
            <button type="submit" class="ms-al-btn-filter">
                Filter
            </button>
            <a href="?" class="ms-al-btn-reset" title="Reset">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>
    </form>
</div>
