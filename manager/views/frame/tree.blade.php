<?php
    $evtOut = evo()->invokeEvent('OnManagerTreeInit', $_REQUEST);
    if (is_array($evtOut)) {
        echo implode("\n", $evtOut);
    }
?>

<div class="treeframebody">
    <div id="treeMenu">
        <a class="treeButton" id="treeMenu_expandtree" onclick="modx.tree.expandTree();" title="{{ ManagerTheme::getLexicon('expand_tree') }}"><i class="{{ $_style['icon_arrow_down_circle'] }}"></i></a>
        <a class="treeButton" id="treeMenu_collapsetree" onclick="modx.tree.collapseTree();" title="{{ ManagerTheme::getLexicon('collapse_tree') }}"><i class="{{ $_style['icon_arrow_up_circle'] }}"></i></a>
        @if(evo()->hasPermission('new_document'))
            <a class="treeButton" id="treeMenu_addresource" onclick="modx.tabs({url:'{{ MODX_MANAGER_URL }}?a=4', title: '{{ ManagerTheme::getLexicon('add_resource') }}'});" title="{{ ManagerTheme::getLexicon('add_resource') }}"><i class="{{ $_style['icon_document'] }}"></i></a>
            <a class="treeButton" id="treeMenu_addweblink" onclick="modx.tabs({url:'{{ MODX_MANAGER_URL }}?a=72', title: '{{ ManagerTheme::getLexicon('add_weblink') }}'});" title="{{ ManagerTheme::getLexicon('add_weblink') }}"><i class="{{ $_style['icon_chain'] }}"></i></a>
        @endif
        <a class="treeButton" id="treeMenu_refreshtree" onclick="modx.tree.restoreTree();" title="{{ ManagerTheme::getLexicon('refresh_tree') }}"><i class="{{ $_style['icon_refresh'] }}"></i></a>
        <a class="treeButton" id="treeMenu_sortingtree" onclick="modx.tree.showSorter(event);" title="{{ ManagerTheme::getLexicon('sort_tree') }}"><i class="{{ $_style['icon_sort'] }}"></i></a>
        @if(evo()->hasPermission('edit_document') && evo()->hasPermission('save_document'))
            <a class="treeButton" id="treeMenu_sortingindex" onclick="modx.tabs({url: '{{ MODX_MANAGER_URL }}?a=56&id=0', title: '{{ ManagerTheme::getLexicon('sort_menuindex') }}'});" title="{{ ManagerTheme::getLexicon('sort_menuindex') }}"><i class="{{ $_style['icon_sort_num_asc'] }}"></i></a>
        @endif
        {{-- @if(evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))
            <a class="treeButton" id="treeMenu_openimages" title="{{ ManagerTheme::getLexicon('images_management') }}&#013;{{ ManagerTheme::getLexicon('em_button_shift') }}"><i class="{{ $_style['icon_camera'] }}"></i></a>
        @endif --}}
        {{--@if(evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))
            <a class="treeButton" id="treeMenu_openfiles" title="{{ ManagerTheme::getLexicon('files_management') }}&#013;{{ ManagerTheme::getLexicon('em_button_shift') }}"><i class="{{ $_style['icon_files'] }}"></i></a>
        @endif --}}
        {{-- @if(evo()->hasPermission('edit_template') || evo()->hasPermission('edit_snippet') || evo()->hasPermission('edit_chunk') || evo()->hasPermission('edit_plugin'))
            <a class="treeButton" id="treeMenu_openelements" title="{{ ManagerTheme::getLexicon('element_management') }}&#013;{{ ManagerTheme::getLexicon('em_button_shift') }}"><i class="{{ $_style['icon_elements'] }}"></i></a>
        @endif --}}
        @if(evo()->hasPermission('empty_trash'))
            <a class="treeButton treeButtonDisabled" id="treeMenu_emptytrash" title="{{ ManagerTheme::getLexicon('empty_recycle_bin_empty') }}"><i class="{{ $_style['icon_trash'] }}"></i></a>
        @endif
        {{-- <a class="treeButton" id="treeMenu_theme_dark" onclick="modx.tree.toggleTheme(event)" title="{{ ManagerTheme::getLexicon('manager_theme_mode_title') }}"><i class="{{ $_style['icon_theme'] }}"></i></a> --}}
    </div>

    <div id="treeHolder">
        <?php
            $evtOut = evo()->invokeEvent('OnManagerTreePrerender', $_REQUEST);
            if (is_array($evtOut)) {
                echo implode("\n", $evtOut);
            }
            $siteName = evo()->getPhpCompat()->entities(evo()->getConfig('site_name'));
        ?>

        <div id="node0" class="rootNode"><a class="node" onclick="modx.tree.treeAction(event, 0)" data-id="0" data-title-esc="{{ $siteName }}"><span class="icon"><i class="{{ $_style['icon_sitemap'] }}"></i></span><span class="title">{{ $siteName }}</span></a>
            <div id="treeloader"><i class="{{ $_style['icon_cog'] }} {{ $_style['icon_spin'] }}"></i></div>
        </div>
        <div id="treeRoot0" class="treeRoot"></div>

        <?php
            $evtOut = evo()->invokeEvent('OnManagerTreeRender', $_REQUEST);
            if (is_array($evtOut)) {
                echo implode("\n", $evtOut);
            }
        ?>
    </div>
</div>
