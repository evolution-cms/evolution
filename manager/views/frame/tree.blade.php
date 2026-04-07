<?php
    $evtOut = evo()->invokeEvent('OnManagerTreeInit', $_REQUEST);
    if (is_array($evtOut)) {
        echo implode("\n", $evtOut);
    }
?>

@php
$__iconBackup = [
    'icon_arrow_down_circle' => $_style['icon_arrow_down_circle'],
    'icon_arrow_up_circle' => $_style['icon_arrow_up_circle'],
    'icon_add' => $_style['icon_add'],
    'icon_chain_broken' => $_style['icon_chain_broken'],
    'icon_refresh' => $_style['icon_refresh'],
    'icon_sort' => $_style['icon_sort'],
    'icon_sort_num_asc' => $_style['icon_sort_num_asc'],
    'icon_trash' => $_style['icon_trash'],
];
$_style['icon_arrow_down_circle'] = svg('tabler-arrow-down')->toHtml();
$_style['icon_arrow_up_circle'] = svg('tabler-arrow-up')->toHtml();
$_style['icon_add'] = svg('tabler-file-plus')->toHtml();
$_style['icon_chain_broken'] = svg('tabler-link-plus')->toHtml();
$_style['icon_refresh'] = svg('tabler-refresh')->toHtml();
$_style['icon_sort'] = svg('tabler-caret-up-down')->toHtml();
$_style['icon_sort_num_asc'] = svg('tabler-sort-ascending-letters')->toHtml();
$_style['icon_trash'] = svg('tabler-trash')->toHtml();
@endphp

<div class="treeframebody">
    <div id="treeMenu">
        <a class="treeButton" id="treeMenu_expandtree" onclick="evo.tree.expandTree();" title="{{ ManagerTheme::getLexicon('expand_tree') }}">{!! $_style['icon_arrow_down_circle'] !!}</a>
        <a class="treeButton" id="treeMenu_collapsetree" onclick="evo.tree.collapseTree();" title="{{ ManagerTheme::getLexicon('collapse_tree') }}">{!! $_style['icon_arrow_up_circle'] !!}</a>
        @if(evo()->hasPermission('new_document'))
            <a class="treeButton" id="treeMenu_addresource" onclick="evo.tabs({url:'{{ EVO_MANAGER_URL }}?a=4', title: '{{ ManagerTheme::getLexicon('add_resource') }}'});" title="{{ ManagerTheme::getLexicon('add_resource') }}">{!! $_style['icon_add'] !!}</a>
            <a class="treeButton" id="treeMenu_addweblink" onclick="evo.tabs({url:'{{ EVO_MANAGER_URL }}?a=72', title: '{{ ManagerTheme::getLexicon('add_weblink') }}'});" title="{{ ManagerTheme::getLexicon('add_weblink') }}">{!! $_style['icon_chain_broken'] !!}</a>
        @endif
        <a class="treeButton" id="treeMenu_refreshtree" onclick="evo.tree.restoreTree();" title="{{ ManagerTheme::getLexicon('refresh_tree') }}">{!! $_style['icon_refresh'] !!}</a>
        <a class="treeButton" id="treeMenu_sortingtree" onclick="evo.tree.showSorter(event);" title="{{ ManagerTheme::getLexicon('sort_tree') }}">{!! $_style['icon_sort'] !!}</a>
        @if(evo()->hasPermission('edit_document') && evo()->hasPermission('save_document'))
            <a class="treeButton" id="treeMenu_sortingindex" onclick="evo.tree.openSortMenuIndex();" title="{{ ManagerTheme::getLexicon('sort_menuindex') }}">{!! $_style['icon_sort_num_asc'] !!}</a>
        @endif
        {{-- @if(evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))
            <a class="treeButton" id="treeMenu_openimages" title="{{ ManagerTheme::getLexicon('images_management') }}&#013;{{ ManagerTheme::getLexicon('em_button_shift') }}"><i class="{{ $_style['icon_camera'] }}"></i></a>
        @endif --}}
        {{--@if(evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))
            <a class="treeButton" id="treeMenu_openfiles" title="{{ ManagerTheme::getLexicon('files_management') }}&#013;{{ ManagerTheme::getLexicon('em_button_shift') }}"><i class="{{ $_style['icon_files'] }}"></i></a>
        @endif --}}
        @if(evo()->hasPermission('empty_trash'))
            <a class="treeButton treeButtonDisabled" id="treeMenu_emptytrash" title="{{ ManagerTheme::getLexicon('empty_recycle_bin_empty') }}">{!! $_style['icon_trash'] !!}</a>
        @endif
        {{-- <a class="treeButton" id="treeMenu_theme_dark" onclick="evo.tree.toggleTheme(event)" title="{{ ManagerTheme::getLexicon('manager_theme_mode_title') }}"><i class="{{ $_style['icon_theme'] }}"></i></a> --}}
    </div>

    <div id="treeHolder">
        <?php
            $evtOut = evo()->invokeEvent('OnManagerTreePrerender', $_REQUEST);
            if (is_array($evtOut)) {
                echo implode("\n", $evtOut);
            }
            $siteName = evo()->getConfig('site_name');
        ?>

        <div id="node0" class="rootNode"><a class="node" onclick="evo.tree.treeAction(event, 0)" data-id="0"
            data-title-esc="{{ $siteName }}"><span class="icon"><i
            class="{{ $_style['icon_sitemap'] }}"></i></span><span class="title">{{ $siteName }}</span></a>
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

@php
foreach ($__iconBackup as $__key => $__value) {
    $_style[$__key] = $__value;
}
@endphp
