{{--
    Display a relation entry (causer or subject) with optional open button.

    @props([
        'morphType' => ?string,
        'morphId' => int|string|null,
        'model' => mixed,
        'openLabel' => string,
    ])
--}}

@if ($morphType === null)
    @php
        $systemLabel = e((string) __('moontrail::ui.system'));
    @endphp
    <span class="ms-al-relation-system ms-al-row-value inline-flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
        {!! \MoonShine\MoonTrail\Support\SvgIcons::computer('w-4 h-4', 'flex-shrink-0 text-gray-500 dark:text-gray-400') !!}
        {{ $systemLabel }}
    </span>
@else
    @php
        $className = class_basename($morphType);
        $identifier = $morphId !== null ? '#' . $morphId : '';

        // Extract display name
        $displayNameRaw = '';
        if (is_object($model)) {
            $raw = data_get($model, 'name')
                ?? data_get($model, 'title')
                ?? data_get($model, 'email');

            if (is_scalar($raw) && $raw !== '') {
                $displayNameRaw = '(' . $raw . ')';
            }
        }

        $text = e(trim("{$className} {$identifier} {$displayNameRaw}"));
    @endphp

    <span class="ms-al-row-value font-medium text-gray-800 dark:text-gray-100">{{ $text }}</span>

    @if ($morphId !== null)
        @php
            $detailUrl = null;
            try {
                $resources = moonshine()->getResources();

                foreach ($resources as $resource) {
                    if (
                        method_exists($resource, 'getModel')
                        && $resource->getModel()::class === $morphType
                    ) {
                        $url = toPage(
                            page: \MoonShine\Laravel\Pages\Crud\DetailPage::class,
                            resource: $resource,
                            params: ['resourceItem' => $morphId],
                        );

                        if (is_string($url)) {
                            $detailUrl = $url;
                        }
                        break;
                    }
                }
            } catch (\Throwable) {
                // Resource lookup failed — return null silently
            }
        @endphp

        @if ($detailUrl !== null)
            <a href="{{ e($detailUrl) }}" target="_blank"
                class="ms-al-btn-open inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-md border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800/40 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-150 shadow-sm">
                {!! \MoonShine\MoonTrail\Support\SvgIcons::externalLink('w-3 h-3', 'flex-shrink-0') !!}
                {{ $openLabel }}
            </a>
        @else
            @php
                $noResourceLabel = e((string) __('moontrail::ui.no_resource'));
            @endphp
            <span class="ms-al-relation-no-resource inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 italic">
                {!! \MoonShine\MoonTrail\Support\SvgIcons::info('w-3 h-3', 'flex-shrink-0') !!}
                {{ $noResourceLabel }}
            </span>
        @endif
    @endif
@endif
