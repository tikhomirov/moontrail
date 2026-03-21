{{--
    Display an event badge with icon and color-coded label.

    @props([
        'activity' => Activity,
    ])
--}}
@php
    $event = \MoonShine\MoonTrail\Enums\ActivityEvent::tryFrom((string) $activity->event);

    if ($event instanceof \MoonShine\MoonTrail\Enums\ActivityEvent) {
        $label = e($event->label());
        $color = $event->color();
        $icon = match ($event) {
            \MoonShine\MoonTrail\Enums\ActivityEvent::Created    => \MoonShine\MoonTrail\Support\SvgIcons::created('w-3 h-3', 'flex-shrink-0'),
            \MoonShine\MoonTrail\Enums\ActivityEvent::Updated    => \MoonShine\MoonTrail\Support\SvgIcons::updated('w-3 h-3', 'flex-shrink-0'),
            \MoonShine\MoonTrail\Enums\ActivityEvent::Deleted    => \MoonShine\MoonTrail\Support\SvgIcons::deleted('w-3 h-3', 'flex-shrink-0'),
            \MoonShine\MoonTrail\Enums\ActivityEvent::Restored   => \MoonShine\MoonTrail\Support\SvgIcons::restored('w-3 h-3', 'flex-shrink-0'),
            \MoonShine\MoonTrail\Enums\ActivityEvent::RolledBack => \MoonShine\MoonTrail\Support\SvgIcons::rolledBack('w-3 h-3', 'flex-shrink-0'),
        };
        $msAlEventClass = 'ms-al-event-' . str_replace('_', '-', $event->value);
    } else {
        $label = $activity->event !== null && $activity->event !== ''
            ? e(ucfirst((string) $activity->event))
            : '—';
        $color = 'gray';
        $icon = '';
        $msAlEventClass = 'ms-al-event-unknown';
    }

    $colorClasses = match ($color) {
        'green'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800',
        'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800',
        'red'    => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 ring-1 ring-purple-200 dark:ring-purple-800',
        'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 ring-1 ring-orange-200 dark:ring-orange-800',
        default  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-600',
    };

    $iconHtml = $icon !== '' ? "<span class=\"opacity-80\">$icon</span>" : '';
@endphp

<span class="ms-al-event-badge {{ $msAlEventClass }} inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $colorClasses }}">
    {!! $iconHtml !!}{!! $label !!}
</span>
