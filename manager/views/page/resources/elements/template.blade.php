<?php /** @var EvolutionCMS\Models\SiteTemplate $item */ ?>
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
                <span>
                    <a class="man_el_name site_templates" target="main" data-type="site_templates" data-id="{{ $item->id }}" data-catid="{{ $item->category }}" href="{{ $item->makeUrl('actions.edit') }}">
                        <i class="{{ $_style['icon_template'] }}"></i>
                        @if($item->locked)
                            <i class="{{ $_style['icon_lock'] }}"></i>
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
                    @if(evolutionCMS()->hasPermission('edit_template'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.edit') }}" target="main" title="{{ ManagerTheme::getLexicon('edit_resource') }}">
                            <i class="{{ $_style['icon_edit'] }}"></i>
                        </a>
                    </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('new_template'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.duplicate') }}" target="main" title="{{ ManagerTheme::getLexicon('resource_duplicate') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}')">
                            <i class="{{ $_style['icon_clone'] }}"></i>
                        </a>
                    </li>
                    @endif
                    @if(evolutionCMS()->hasPermission('delete_template'))
                    <li>
                        <a href="{{ $item->makeUrl('actions.delete') }}" target="main" title="{{ ManagerTheme::getLexicon('delete') }}" onclick="return confirm('{{ ManagerTheme::getLexicon('confirm_delete_template') }}')">
                            <i class="{{ $_style['icon_trash'] }}"></i>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</li>
