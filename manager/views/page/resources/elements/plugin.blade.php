<?php /** @var EvolutionCMS\Models\SitePlugin $item */ ?>
<li>
    <div class="rTable">
        <div class="rTableRow">
            @if(!empty($item->isAlreadyEdit))
                <div class="lockCell">
                    <?php $rowLock = $item->alreadyEditInfo; ?>
                    <span title="{{ str_replace(['[+lasthit_df+]', '[+element_type+]'], [$rowLock['lasthit_df'], ManagerTheme::getLexicon('lock_element_type_2')], ManagerTheme::getLexicon('lock_element_editing')) }}" class="editResource" style="cursor:context-menu;">
                        <i class="{{ $_style['icon_eye'] }}"></i>
                    </span>&nbsp;
                </div>
            @endif
            <div class="mainCell elements_description">
                <span class="rTableRowTitle @if($item->disabled) disabledPlugin @endif">
                    <a class="man_el_name site_plugins" target="main" data-type="site_plugins" data-id="{{ $item->id }}" data-catid="{{ $item->category }}" href="{{ $item->makeUrl('actions.edit') }}">
                        <i class="{{ $_style['icon_plugin'] }}"></i>
                        @if($item->locked)
                            <i class="{{ $_style['icon_lock']  }}"></i>
                        @endif
                        {{ $item->name }}
                        <small>({{ $item->id }})</small>
                        <span class="elements_descr">
                            {{ $item->caption }}
                            {{ safe_html($item->description) }}
                        </span>
                    </a>
                    @if(ManagerTheme::getTextDir() !== 'ltr')
                    &rlm;
                    @endif
                </span>
            </div>
            <div class="btnCell">
                <ul class="elements_buttonbar">
                    @if(evolutionCMS()->hasPermission('edit_plugin'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.edit') }}" target="main" title="{{ ManagerTheme::getLexicon('edit_resource') }}">
                            <i class="{{ $_style['icon_edit'] }}"></i>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:;"
                           onclick="actionDisableElement(this)"
                           title="@if($item->disabled) {{ ManagerTheme::getLexicon('enable') }} @else {{ ManagerTheme::getLexicon('disable') }} @endif"
                           data-disabled="{{ $item->disabled }}"
                           data-enable-href="{{ $item->makeUrl('actions.enable', false, ['disabled' => 0]) }}"
                           data-enable-title="{{ ManagerTheme::getLexicon('enable') }}"
                           data-enable-icon="{{ $_style['icon_enable'] }}"
                           data-disable-href="{{ $item->makeUrl('actions.disable', false, ['disabled' => 1]) }}"
                           data-disable-title="{{ ManagerTheme::getLexicon('disable') }}"
                           data-disable-icon="{{ $_style['icon_disable'] }}"
                        >
                            <i class="@if($item->disabled) {{ $_style['icon_enable'] }} @else {{ $_style['icon_disable'] }} @endif"></i>
                        </a>
                    </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('new_plugin'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.duplicate') }}" target="main" title="{{ ManagerTheme::getLexicon('resource_duplicate') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}')">
                            <i class="{{ $_style['icon_clone'] }}"></i>
                        </a>
                    </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('delete_plugin'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.delete') }}" target="main" title="{{ ManagerTheme::getLexicon('delete') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_delete_plugin') }}')">
                            <i class="{{ $_style['icon_trash'] }}"></i>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</li>
