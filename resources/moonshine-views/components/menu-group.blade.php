@props([
    'label' => '',
    'previewLabel' => '',
    'icon' => '',
    'onlyIcon' => false,
    'items' => [],
    'isActive' => false,
    'top' => false,
    'defaultOpen' => false,
])
<li {{ $attributes->class(['menu-item']) }}
    @if($top)
        x-data="{ dropdown: false }"
        @click.outside="dropdown = false"
        data-dropdown-placement="bottom-start"
    @else
        {{-- Open only when the current page is Logs (or default_open). Do not persist via localStorage:
             that kept the group expanded on unrelated admin pages after visiting Logs once. --}}
        x-data="{
            dropdown: {{ ($isActive || $defaultOpen) ? 'true' : 'false' }},
            toggle() {
                this.dropdown = !this.dropdown;
                $nextTick(() => {
                    if (this.dropdown && $refs.dropdownMenu) {
                        $refs.dropdownMenu.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                });
            }
        }"
        x-init="(() => { try { localStorage.removeItem('moontrail_menu_open') } catch (e) {} })()"
    @endif
    :class="dropdown && 'menu-item--opened'"
    x-ref="dropdownMenu"
>
    <button
        @if(!$top)
            x-data="navTooltip"
            @mouseenter="toggleTooltip()"
            @click.prevent="toggle()"
        @else
            @click.prevent="dropdown = ! dropdown"
        @endif
        class="menu-button{{ $isActive ? ' _is-active' : '' }}"
        type="button"
    >
        @if($icon)
            <div class="menu-icon">
                {!! $icon !!}
            </div>
        @else
            <div class="menu-icon">
                <x-moonshine::icon icon="folder" x-show="!dropdown" />
                <x-moonshine::icon icon="folder-open" x-show="dropdown" />
            </div>
        @endif

        <span class="menu-text @if($onlyIcon) menu-only-icon @endif">{{ $label }}</span>
        <span class="menu-arrow">
            <x-moonshine::icon
                icon="chevron-down"
            />
        </span>
    </button>

    @if($items)
        <x-moonshine::menu
            :dropdown="true"
            :items="$items"
            x-transition.top=""
            style="display: none"
            x-show="dropdown"
        />
    @endif
</li>
