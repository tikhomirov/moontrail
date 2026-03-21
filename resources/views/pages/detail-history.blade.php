@php
/** @var \MoonShine\MoonTrail\Components\ActivityTimeline $timeline */
@endphp
<div class="ms-al-card">
    @include('moontrail::components.section-header', ['type' => 'history'])
    <div class="p-4">
        {!! $timeline->render() !!}
    </div>
</div>
