<div x-data="{ open: false, versionId: null, versionNum: null, versionAt: null, dark: document.documentElement.classList.contains('dark') }"
     @open-rollback-modal.window="dark = document.documentElement.classList.contains('dark'); open = true; versionId = $event.detail.versionId; versionNum = $event.detail.versionNum; versionAt = $event.detail.versionAt ?? null"
     @keydown.escape.window="open = false">

    <template x-teleport="body">
        <div x-show="open" x-cloak
             :style="open ? 'position:fixed;top:0;right:0;bottom:0;left:0;z-index:9999;' : 'display:none'">

            {{-- Backdrop --}}
            <div style="position:fixed;top:0;right:0;bottom:0;left:0;z-index:1;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);"
                 @click="open = false" aria-hidden="true"></div>

            {{-- Dialog card — centered via fixed + transform to avoid MoonShine body containing-block issue --}}
            <div :style="'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:2;max-width:28rem;width:calc(100% - 2rem);padding:1.5rem;border-radius:0.75rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);'
                    + (dark ? 'background:#1f2937;color:#f3f4f6;' : 'background:#ffffff;color:#111827;')"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="ms-al-rollback-title"
                 aria-describedby="ms-al-rollback-desc"
                 x-transition.opacity.duration.200ms>

                {{-- Header --}}
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
                    <div :style="'flex-shrink:0;width:2.5rem;height:2.5rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;color:#ea580c;'
                            + (dark ? 'background:rgba(154,52,18,0.4);' : 'background:#ffedd5;')"
                         aria-hidden="true">
                        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="ms-al-rollback-title"
                            :style="'font-size:1.125rem;line-height:1.75rem;font-weight:600;margin:0;' + (dark ? 'color:#ffffff;' : 'color:#111827;')">
                            {{ __('moontrail::ui.rollback_confirm_title') }}
                        </h3>
                        <p id="ms-al-rollback-desc"
                           :style="'font-size:0.875rem;line-height:1.25rem;margin:0.125rem 0 0 0;' + (dark ? 'color:#9ca3af;' : 'color:#6b7280;')">
                            {{ __('moontrail::ui.rollback_confirm_text') }}
                            <strong x-text="'#' + versionNum" style="color:#ea580c;"></strong>
                        </p>
                        <p x-show="versionAt" x-cloak
                           :style="'font-size:0.75rem;line-height:1rem;margin:0.125rem 0 0 0;font-variant-numeric:tabular-nums;' + (dark ? 'color:#6b7280;' : 'color:#9ca3af;')"
                           x-text="versionAt"></p>
                    </div>
                </div>

                {{-- Warning box --}}
                <div :style="'border-radius:0.5rem;padding:0.75rem;margin-bottom:1rem;'
                        + (dark ? 'background:rgba(154,52,18,0.15);border:1px solid rgba(146,64,14,0.5);' : 'background:#fff7ed;border:1px solid #fed7aa;')"
                     role="note">
                    <p :style="'font-size:0.75rem;line-height:1.125rem;margin:0 0 0.25rem 0;' + (dark ? 'color:#fdba74;' : 'color:#c2410c;')">
                        {{ __('moontrail::ui.rollback_confirm_snapshot_note') }}
                    </p>
                    <p :style="'font-size:0.75rem;line-height:1.125rem;margin:0 0 0.25rem 0;' + (dark ? 'color:#fdba74;' : 'color:#c2410c;')">
                        {{ __('moontrail::ui.rollback_confirm_overwrite_warning') }}
                    </p>
                    <p :style="'font-size:0.75rem;line-height:1.125rem;margin:0;' + (dark ? 'color:#fdba74;' : 'color:#c2410c;')">
                        {{ __('moontrail::ui.rollback_confirm_history_warning') }}
                    </p>
                </div>

                {{-- Buttons --}}
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.75rem;">
                    <button type="button" @click="open = false"
                            aria-label="{{ __('moontrail::ui.cancel') }}"
                            :style="'padding:0.5rem 1rem;font-size:0.875rem;line-height:1.25rem;font-weight:500;border-radius:0.5rem;cursor:pointer;transition:background-color 0.15s;'
                                + (dark ? 'background:#374151;color:#d1d5db;border:1px solid #4b5563;' : 'background:#ffffff;color:#374151;border:1px solid #d1d5db;')"
                            @mouseover="$el.style.backgroundColor = dark ? '#4b5563' : '#f9fafb'"
                            @mouseout="$el.style.backgroundColor = dark ? '#374151' : '#ffffff'">
                        {{ __('moontrail::ui.cancel') }}
                    </button>

                    <form method="POST" action="{{ route('moonshine.moontrail.rollback') }}">
                        @csrf
                        <input type="hidden" name="modelVersion" :value="versionId">
                        <button type="submit"
                                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;font-size:0.875rem;line-height:1.25rem;font-weight:500;border-radius:0.5rem;background:#ea580c;color:#ffffff;border:none;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.05);transition:background-color 0.15s;"
                                @mouseover="$el.style.backgroundColor='#c2410c'"
                                @mouseout="$el.style.backgroundColor='#ea580c'">
                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ __('moontrail::ui.rollback_confirm_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
