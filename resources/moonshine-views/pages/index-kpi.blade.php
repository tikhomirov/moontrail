@php
/**
 * @var array<int, array{label: string, value: string, modifier: string, icon: string, href: ?string, isActive: bool}> $cards
 */
@endphp
<div class="ms-al-kpi-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
    @foreach($cards as $card)
        @php
            $activeClass = $card['isActive'] ? ' ms-al-kpi-card--active ring-2 ring-offset-1 dark:ring-offset-gray-900' : '';
            $cursorClass = $card['href'] !== null ? ' cursor-pointer hover:border-gray-300 dark:hover:border-gray-600 transition-colors' : '';
        @endphp
        @if($card['href'] !== null)
            <a href="{{ $card['href'] }}" class="ms-al-kpi-card {{ $card['modifier'] }}{{ $activeClass }}{{ $cursorClass }} flex items-center gap-3 px-4 py-3 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/80 shadow-sm no-underline">
                <div class="ms-al-kpi-icon flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center">
                    {!! $card['icon'] !!}
                </div>
                <div class="min-w-0">
                    <div class="ms-al-kpi-value text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $card['value'] }}</div>
                    <div class="ms-al-kpi-label text-xs text-gray-500 dark:text-gray-400 truncate">{{ $card['label'] }}</div>
                </div>
            </a>
        @else
            <div class="ms-al-kpi-card {{ $card['modifier'] }} flex items-center gap-3 px-4 py-3 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/80 shadow-sm">
                <div class="ms-al-kpi-icon flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center">
                    {!! $card['icon'] !!}
                </div>
                <div class="min-w-0">
                    <div class="ms-al-kpi-value text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $card['value'] }}</div>
                    <div class="ms-al-kpi-label text-xs text-gray-500 dark:text-gray-400 truncate">{{ $card['label'] }}</div>
                </div>
            </div>
        @endif
    @endforeach
</div>
