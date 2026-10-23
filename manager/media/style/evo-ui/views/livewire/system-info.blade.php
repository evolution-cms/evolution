<main class="evo-system-info" data-manager-screen="system-info" aria-label="{{ ManagerTheme::getLexicon('view_sysinfo') }}">
    <style>
        .evo-system-info { padding: 12px; min-width: 0; }
        .evo-system-info table { width: 100%; min-width: 0; table-layout: fixed; background: transparent; }
        .evo-system-info th { width: 32%; text-align: start; font-weight: 600; }
        .evo-system-info th, .evo-system-info td { white-space: normal; overflow-wrap: anywhere; }
        .evo-system-info tbody tr:last-child th, .evo-system-info tbody tr:last-child td { border-bottom: 0; }
        .evo-opcache-info { display: flex; align-items: center; gap: 12px; }
        .evo-opcache-summary { flex: 1; min-width: 0; display: flex; align-items: baseline; flex-wrap: wrap; gap: 2px 10px; }
        .evo-system-info .evo-opcache-reset { flex: 0 0 auto; width: 10.5em; max-width: 100%; }
        .evo-system-info-status { font-weight: 650; white-space: nowrap; }
        .evo-system-info-status--enabled { color: var(--evo-ui-success); }
        .evo-system-info-status--disabled { color: var(--evo-ui-danger); }
        .evo-system-info-details { color: var(--evo-ui-muted); }
        .evo-system-info-details { font-size: .9em; }
        .evo-opcache-summary__state { display: inline-flex; align-items: baseline; flex-wrap: wrap; gap: 0 8px; }
        .evo-opcache-summary__memory { display: inline-block; white-space: nowrap; }
        .evo-system-info-status--disabled { font-weight: 600; }
        .evo-system-info-version { color: var(--evo-ui-primary); font-weight: 700; }
        .evo-system-info-date { font-weight: 600; }
        .evo-system-info-time { display: inline-flex; align-items: center; gap: 6px; padding: 3px 7px; border-radius: 5px; background: var(--evo-ui-surface); font-variant-numeric: tabular-nums; }
        .evo-system-info-time svg { width: 16px; height: 16px; color: var(--evo-ui-muted); }
        .evo-copy-value { display: flex; align-items: center; gap: 8px; width: 100%; min-width: 0; }
        .evo-copy-value__text { display: flex; flex: 1; min-width: 0; overflow: hidden; white-space: nowrap; }
        .evo-copy-value__head { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
        .evo-copy-value__tail { flex: 0 0 auto; max-width: 70%; overflow: hidden; text-overflow: ellipsis; }
        .evo-copy-value .evo-copy-value__button { flex: 0 0 26px; width: 26px; min-width: 26px; height: 26px; min-height: 26px; padding: 0; margin-inline-start: auto; }
        .evo-copy-value__button [x-cloak] { display: none !important; }
        .evo-opcache-reset [role="status"] { white-space: nowrap; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
        @media (max-width: 600px) {
            .evo-system-info { padding: 8px; }
        }
        @media (max-width: 420px) {
            .evo-opcache-info { flex-wrap: wrap; gap: 6px; }
            .evo-opcache-summary { flex-basis: 100%; }
        }
    </style>

    <table class="evo-ui-table" aria-label="{{ ManagerTheme::getLexicon('view_sysinfo') }}">
        <tbody>
            @foreach ($serverArr as $key => $value)
                <tr>
                    <th scope="row">{{ empty($value['is_lexicon']) ? $key : ManagerTheme::getLexicon($key) }}</th>
                    <td>
                        @if ($key === 'PHP Version')
                            <x-evo::button :label="$value['data'] . ' · phpinfo'" x-on:click="$wire.openPhpInfo($el.ownerDocument.documentElement.dataset.theme)" wire:loading.attr="disabled" wire:target="openPhpInfo" :disabled="!function_exists('phpinfo')" />
                        @elseif ($key === 'opcache')
                            <div class="evo-opcache-info">
                                @if ($opcacheInfo['can_reset'])
                                    <x-evo::button icon="refresh" tone="primary" class="evo-opcache-reset"
                                        :data-label="ManagerTheme::getLexicon('opcache_reset')"
                                        x-data="{ defaultLabel: $el.dataset.label, label: $el.dataset.label, result: null, timer: null }"
                                        x-on:evo-system-info-opcache-reset.window="clearTimeout(timer); label = $event.detail.message; result = $event.detail.successful; timer = setTimeout(() => { label = defaultLabel; result = null; }, 2500)"
                                        x-bind:title="label"
                                        x-bind:class="{ 'evo-ui-btn--success': result === true, 'evo-ui-btn--danger': result === false }"
                                        wire:click="resetOpcache" wire:loading.attr="disabled" wire:target="resetOpcache">
                                        <span role="status" aria-live="polite" wire:loading.remove wire:target="resetOpcache" x-text="label">{{ ManagerTheme::getLexicon('opcache_reset') }}</span>
                                        <span wire:loading wire:target="resetOpcache">{{ ManagerTheme::getLexicon('opcache_resetting') }}</span>
                                    </x-evo::button>
                                @endif
                                <div class="evo-opcache-summary" title="{{ $opcacheInfo['details'] }}">
                                    <div class="evo-opcache-summary__state">
                                        <strong class="evo-system-info-status evo-system-info-status--{{ $opcacheInfo['enabled'] ? 'enabled' : 'disabled' }}">{{ ManagerTheme::getLexicon($opcacheInfo['enabled'] ? 'enabled' : 'disabled') }}</strong>
                                        @if ($opcacheInfo['hit_rate'] !== null)
                                            <span class="evo-system-info-details">{{ sprintf(ManagerTheme::getLexicon('sysinfo_opcache_hit_rate'), $opcacheInfo['hit_rate']) }}</span>
                                        @endif
                                    </div>
                                    @if ($opcacheInfo['has_statistics'])
                                        <span class="evo-system-info-details evo-opcache-summary__memory" aria-label="{{ sprintf(ManagerTheme::getLexicon('sysinfo_opcache_memory'), $opcacheInfo['memory_used'], $opcacheInfo['memory_total']) }}">{{ $opcacheInfo['memory_used'] }} / {{ $opcacheInfo['memory_total'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @elseif (isset($value['status']))
                            <strong class="evo-system-info-status evo-system-info-status--{{ $value['status'] ? 'enabled' : 'disabled' }}">{{ ManagerTheme::getLexicon($value['status'] ? 'enabled' : 'disabled') }}</strong>
                            @if (isset($value['details']))
                                <span class="evo-system-info-details">{{ $value['details'] }}</span>
                            @endif
                        @elseif (in_array($key, ['evo_version', 'database_version'], true))
                            <strong class="evo-system-info-version">{{ $value['data'] }}</strong>
                        @elseif ($key === 'release_date')
                            <strong class="evo-system-info-date">{{ $value['data'] }}</strong>
                        @elseif (in_array($key, ['servertime', 'localtime'], true))
                            <time class="evo-system-info-time" datetime="{{ $value['data'] }}"><x-evo::icon name="clock" />{{ $value['data'] }}</time>
                        @elseif (in_array($key, ['database_name', 'database_server', 'table_prefix', 'cfg_base_path', 'cfg_base_url', 'cfg_manager_url', 'cfg_manager_path', 'cfg_site_url'], true) && (string)$value['data'] !== '')
                            @include('manager::partials.sysinfo-copy-value', ['value' => $value['data']])
                        @elseif (isset($value['render']))
                            @include($value['render'], ['data' => $value['data']])
                        @else
                            {{ $value['data'] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <x-evo::modal :open="$phpInfoOpen" title="PHP Info" icon="info-circle" size="3xl">
        @if($phpInfoOpen)
            <iframe title="PHP Info" sandbox referrerpolicy="no-referrer" srcdoc="{{ $phpInfo }}"
                style="width: 100%; height: min(70vh, 800px); border: 0;"></iframe>
        @endif
    </x-evo::modal>
</main>
