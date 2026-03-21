{{--
    Display a section header with icon and title.

    @props([
        'type' => string,  // 'general', 'relations', 'changes', 'history'
    ])
--}}
@php
    $icon = match ($type) {
        'general'   => \MoonShine\MoonTrail\Support\SvgIcons::info(),
        'relations' => \MoonShine\MoonTrail\Support\SvgIcons::link(),
        'changes'   => \MoonShine\MoonTrail\Support\SvgIcons::diff(),
        'history'   => \MoonShine\MoonTrail\Support\SvgIcons::clock(),
        default     => '',
    };

    $title = match ($type) {
        'general'   => e((string) __('moontrail::ui.section_general')),
        'relations' => e((string) __('moontrail::ui.section_relations')),
        'changes'   => e((string) __('moontrail::ui.section_changes')),
        'history'   => e((string) __('moontrail::ui.section_history')),
        default     => '',
    };
@endphp

<div class="ms-al-section-header flex items-center gap-2.5 px-4 py-3 bg-gray-50/80 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700/70">
    <div class="ms-al-section-icon flex-shrink-0 w-7 h-7 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400 shadow-sm">
        {!! $icon !!}
    </div>
    <h3 class="ms-al-section-title text-sm font-bold text-gray-700 dark:text-gray-200 tracking-wide">{{ $title }}</h3>
</div>
