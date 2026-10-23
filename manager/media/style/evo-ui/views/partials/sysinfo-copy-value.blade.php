@php
    $value = (string)$value;
    $withoutTrailingSlash = rtrim($value, '/\\');
    $slash = strrpos($withoutTrailingSlash, '/');
    $backslash = strrpos($withoutTrailingSlash, '\\');
    $separator = max($slash === false ? -1 : $slash, $backslash === false ? -1 : $backslash);
    $head = $separator < 0 ? '' : substr($value, 0, $separator);
    $tail = $separator < 0 ? $value : substr($value, $separator);
@endphp
<div class="evo-copy-value"
    data-copy-label="{{ ManagerTheme::getLexicon('sysinfo_copy') }}"
    data-copy-success="{{ ManagerTheme::getLexicon('sysinfo_copied') }}"
    data-copy-failed="{{ ManagerTheme::getLexicon('sysinfo_copy_failed') }}"
    x-data="{
        result: null, busy: false, timer: null,
        async copy(value) {
            this.busy = true;
            const active = this.$el.ownerDocument.activeElement;
            const input = this.$el.ownerDocument.createElement('textarea');
            input.value = value;
            input.readOnly = true;
            input.style.cssText = 'position:fixed;left:-9999px;top:0;';
            this.$el.ownerDocument.body.appendChild(input);
            input.focus({ preventScroll: true });
            input.select();
            input.setSelectionRange(0, value.length);
            const onCopy = event => {
                if (event.clipboardData) {
                    event.clipboardData.setData('text/plain', value);
                    event.preventDefault();
                }
            };
            this.$el.ownerDocument.addEventListener('copy', onCopy);
            let copied = false;
            try { copied = this.$el.ownerDocument.execCommand('copy'); } catch {}
            finally { this.$el.ownerDocument.removeEventListener('copy', onCopy); }
            input.remove();
            if (active) active.focus({ preventScroll: true });
            try {
                await navigator.clipboard.writeText(value);
                this.result = 'success';
            } catch { this.result = copied ? 'success' : 'error'; }
            finally {
                this.busy = false;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.result = null, 2500);
            }
        },
        destroy() { clearTimeout(this.timer); }
    }">
    <span class="evo-copy-value__text" title="{{ $value }}">
        @if ($head !== '')<span class="evo-copy-value__head">{{ $head }}</span>@endif<span class="evo-copy-value__tail">{{ $tail }}</span>
    </span>
    <x-evo::button :icon-only="true" class="evo-copy-value__button"
        :data-value="$value" :aria-label="ManagerTheme::getLexicon('sysinfo_copy')" :title="ManagerTheme::getLexicon('sysinfo_copy')"
        x-on:click="copy($el.dataset.value)" x-bind:disabled="busy"
        x-bind:title="result === 'success' ? $root.dataset.copySuccess : result === 'error' ? $root.dataset.copyFailed : $root.dataset.copyLabel"
        x-bind:class="{ 'evo-ui-btn--success': result === 'success', 'evo-ui-btn--danger': result === 'error' }">
        <x-evo::icon name="copy" class="evo-ui-btn__icon" x-show="result === null" />
        <x-evo::icon name="check" class="evo-ui-btn__icon" x-show="result === 'success'" x-cloak />
        <x-evo::icon name="x" class="evo-ui-btn__icon" x-show="result === 'error'" x-cloak />
    </x-evo::button>
    <span class="evo-ui-sr-only" role="status" aria-live="polite" x-text="result === 'success' ? $root.dataset.copySuccess : result === 'error' ? $root.dataset.copyFailed : ''"></span>
</div>
