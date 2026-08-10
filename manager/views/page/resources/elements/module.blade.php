<?php /** @var EvolutionCMS\Models\SiteModule $item */ ?>
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
                    @if(empty($action))
                        <span class="man_el_name">
                    @else
                        <a class="man_el_name site_modules" target="main" data-type="site_modules" data-id="{{ $item->id }}" data-catid="{{ $item->category }}" href="{{ $item->makeUrl($action) }}">
                    @endif
                        @if(empty($item->icon))
                            <i class="{{ $_style['icon_module'] }}"></i>
                        @else
                            <i class="{{ $item->icon }}"></i>
                        @endif
                        @if($item->locked)
                            <i class="{{ $_style['icon_lock'] }}"></i>
                        @endif
                        {{ $item->name }}
                        <small>({{ $item->id }})</small>
                        <span class="elements_descr">
                            {{ $item->caption }}
                            {{ safe_html($item->description) }}
                        </span>
                    @if(empty($action))
                        </span>
                    @else
                        </a>
                    @endif
                    @if(ManagerTheme::getTextDir() !== 'ltr')
                    &rlm;
                    @endif
                </span>
            </div>
            <div class="btnCell">
                <ul class="elements_buttonbar">
                    @if(evolutionCMS()->hasPermission('exec_module'))
                        <li>
                            <a href="{{ $item->makeUrl('actions.run') }}" target="main" title="{{ ManagerTheme::getLexicon('run_module') }}">
                                <i class="{{ $_style['icon_play'] }}"></i>
                            </a>
                        </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('edit_module'))
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
                    @if(evolutionCMS()->hasPermission('new_module') && evolutionCMS()->hasPermission('save_module'))
                        <li>
                            <a href="{{ $item->makeUrl('actions.duplicate') }}" target="main" title="{{ ManagerTheme::getLexicon('resource_duplicate') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}')">
                                <i class="{{ $_style['icon_clone'] }}"></i>
                            </a>
                        </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('delete_module'))
                        <li>
                            <a href="{{ $item->makeUrl('actions.delete') }}" target="main" title="{{ ManagerTheme::getLexicon('delete') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_delete_module') }}')">
                                <i class="{{ $_style['icon_trash'] }}"></i>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</li>
