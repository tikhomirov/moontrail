{{--
    Display an entity link (for index fields like causer/subject).

    @props([
        'morphType' => ?string,
        'morphId' => int|string|null,
        'model' => mixed,
    ])
--}}
@if ($morphType === null)
    @php
        $systemLabel = e((string) __('moontrail::ui.system'));
    @endphp
    <span class="text-gray-400 dark:text-gray-500 text-xs italic">{{ $systemLabel }}</span>
@else
    @php
        $className = class_basename($morphType);
        $identifier = $morphId !== null ? '#' . $morphId : '';

        // Extract display name
        $nameRaw = '';
        if (is_object($model)) {
            $raw = data_get($model, 'name')
                ?? data_get($model, 'title')
                ?? data_get($model, 'email');

            if (is_scalar($raw) && $raw !== '') {
                $nameRaw = '(' . $raw . ')';
            }
        }

        $text = e(trim("{$className} {$identifier} {$nameRaw}"));
    @endphp

    @if ($morphId !== null)
        @php
            $url = null;
            try {
                $resources = moonshine()->getResources();

                foreach ($resources as $resource) {
                    if (
                        method_exists($resource, 'getModel')
                        && $resource->getModel()::class === $morphType
                    ) {
                        $urlResult = toPage(
                            page: \MoonShine\Laravel\Pages\Crud\DetailPage::class,
                            resource: $resource,
                            params: ['resourceItem' => $morphId],
                        );

                        if (is_string($urlResult)) {
                            $url = $urlResult;
                        }
                        break;
                    }
                }
            } catch (\Throwable) {
                // Resource lookup failed — return null silently
            }
        @endphp

        @if ($url !== null)
            <a href="{{ e($url) }}" class="ms-al-index-link inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline font-medium text-sm">
                {!! \MoonShine\MoonTrail\Support\SvgIcons::externalLink('w-3 h-3', 'flex-shrink-0 opacity-60') !!}
                {{ $text }}
            </a>
        @else
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $text }}</span>
        @endif
    @else
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $text }}</span>
    @endif
@endif
