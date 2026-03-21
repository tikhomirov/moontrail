@php
/** @var array<int, \MoonShine\MoonTrail\Diff\FieldChange> $changes */
@endphp
<div class="ms-al-card">
    @include('moontrail::components.section-header', ['type' => 'changes'])
    <div class="p-4">
        @include('moontrail::components.diff-viewer', ['changes' => $changes, 'compact' => false])
    </div>
</div>
