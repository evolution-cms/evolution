<?php
$enable_filter = evo()->getConfig('enable_filter');
evo()->setConfig('enable_filter', true);
$_style = ManagerTheme::getStyle();
$wrapActionIcon = function (string $svg, string $class = 'action-icon'): string {
    return '<span class="' . $class . '">' . $svg . '</span>';
};
$_style['icon_edit'] = $wrapActionIcon(svg('tabler-file-pencil')->toHtml());
$_style['icon_eye'] = $wrapActionIcon(svg('tabler-eye')->toHtml());
$_style['icon_trash'] = $wrapActionIcon(svg('tabler-trash')->toHtml(), 'action-icon action-icon-trash');
$_style['icon_undo'] = $wrapActionIcon(svg('tabler-arrow-back-up')->toHtml());
$_style['icon_check'] = $wrapActionIcon(svg('tabler-check')->toHtml());
$_style['icon_close'] = $wrapActionIcon(svg('tabler-x')->toHtml());
$_style['icon_info'] = $wrapActionIcon(svg('tabler-info-square-rounded')->toHtml());
$contents = \EvolutionCMS\Models\SiteContent::query()->orderBy('editedon','DESC')->limit(10);

if ($contents->count() < 1) {
    return '<tr><td>[%no_activity_message%]</td></tr>';
}

$tpl = '<tr>
    <td data-toggle="collapse" data-target=".collapse[+id+]" class="text-nowrap text-right"><span class="label label-info">[+id+]</span></td>
    <td data-toggle="collapse" data-target=".collapse[+id+]"><a class="[+status+]" title="[%edit_resource%]" href="index.php?a=3&amp;id=[+id+]" target="main">[+pagetitle:htmlentities+]</a></td>
    <td data-toggle="collapse" data-target=".collapse[+id+]" class="text-right text-nowrap">[+edit_date+]</td>
    <td data-toggle="collapse" data-target=".collapse[+id+]">[+username:htmlentities+]</td>
    <td style="text-align: right;" class="actions">[+edit_btn+][+publish_btn+][+delete_btn+][+info_btn+][+preview_btn+]</td>
</tr>
<tr class="resource-overview-accordian collapse collapse[+id+]">
    <td colspan="6">
        <div class="overview-body text-small">
            <ul>
                <li><b>[%long_title%]</b>: [+longtitle+]</li>
                <li><b>[%description%]</b>: [+description+]</li>
                <li><b>[%resource_summary%]</b>: [+introtext+]</li>
                <li><b>[%type%]</b>: [+doctype+]</li>
                <li><b>[%resource_alias%]</b>: [+alias+]</li>
                <li><b>[%page_data_cacheable%]</b>: [+cacheable+]</li>
                <li><b>[%resource_opt_show_menu%]</b>: [+hidemenu+]</li>
                <li><b>[%page_data_template%]</b>: [+template:templatename:htmlentities+]</li>
            </ul>
        </div>
    </td>
</tr>';

$btntpl['edit'] = '<a title="[%edit_resource%]" href="index.php?a=27&amp;id=[+id+]" target="main">' . $_style['icon_edit'] . '</a> ';
$btntpl['preview_btn'] = '<a class="[+preview_disabled+]" title="[%preview_resource%]" target="_blank" href="../index.php?&amp;id=[+id+]">' . $_style['icon_eye'] . '</a> ';

$output = [];
foreach ($contents->get()->toArray() as $ph) {
    $docid = $ph['id'];
    $_ = evo()->getUserInfo($ph['editedby']);
    if (isset($_['username'])) {
        $ph['username'] = $_['username'];
    }

    if ($ph['deleted'] == 1) {
        $ph['status'] = 'deleted text-danger';
    } elseif ($ph['published'] == 0) {
        $ph['status'] = 'unpublished font-italic text-muted';
    } else {
        $ph['status'] = 'published';
    }

    if (evo()->hasPermission('edit_document')) {
        $ph['edit_btn'] = str_replace('[+id+]', $docid, $btntpl['edit']);
    } else {
        $ph['edit_btn'] = '';
    }

    $preview_disabled = ($ph['deleted'] == 1) ? 'disabled' : '';
    $ph['preview_btn'] = str_replace([
        '[+id+]',
        '[+preview_disabled+]'
    ], [
        $docid,
        $preview_disabled
    ], $btntpl['preview_btn']);

    if (evo()->hasPermission('delete_document')) {
        if ($ph['deleted'] == 0) {
            $delete_btn = '<a onclick="return confirm(\'[%confirm_delete_record%]\')" title="[%delete_resource%]" href="index.php?a=6&amp;id=[+id+]" target="main">' . $_style['icon_trash'] . '</a> ';
        } else {
            $delete_btn = '<a onclick="return confirm(\'[%confirm_undelete%]\')" title="[%undelete_resource%]" href="index.php?a=63&amp;id=[+id+]" target="main">' . $_style['icon_undo'] . '</a> ';
        }
        $ph['delete_btn'] = str_replace('[+id+]', $docid, $delete_btn);
    } else {
        $ph['delete_btn'] = '';
    }

    if ($ph['deleted'] == 1 && $ph['published'] == 0) {
        $publish_btn = '<a class="disabled" title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main">' . $_style['icon_check'] . '</a> ';
    } elseif ($ph['deleted'] == 1 && $ph['published'] == 1) {
        $publish_btn = '<a class="disabled" title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main">' . $_style['icon_close'] . '</a> ';
    } elseif ($ph['deleted'] == 0 && $ph['published'] == 0) {
        $publish_btn = '<a title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main">' . $_style['icon_check'] . '</a> ';
    } else {
        $publish_btn = '<a title="[%unpublish_resource%]" href="index.php?a=62&amp;id=[+id+]" target="main">' . $_style['icon_close'] . '</a> ';
    }

    $ph['publish_btn'] = str_replace('[+id+]', $docid, $publish_btn);
    $ph['info_btn'] = str_replace('[+id+]', $docid, '<a title="[%resource_overview%]" data-toggle="collapse" data-target=".collapse[+id+]">' . $_style['icon_info'] . '</a>');
    $ph['longtitle'] = $ph['longtitle'] == '' ? '(<i>[%not_set%]</i>)' : entities($ph['longtitle']);
    $ph['description'] = $ph['description'] == '' ? '(<i>[%not_set%]</i>)' : entities($ph['description']);
    $ph['introtext'] = $ph['introtext'] == '' ? '(<i>[%not_set%]</i>)' : entities($ph['introtext']);
    $ph['alias'] = $ph['alias'] == '' ? '(<i>[%not_set%]</i>)' : entities($ph['alias']);
    $ph['edit_date'] = evo()->toDateFormat(evo()->timestamp($ph['editedon']));
    $ph['doctype'] = $ph['type'] == 'reference' ? '[%weblink%]' : '[%resource%]';
    $ph['hidemenu'] = $ph['hidemenu'] == 1 ? '[%no%]' : '[%yes%]';
    $ph['cacheable'] = $ph['cacheable'] == 1 ? '[%yes%]' : '[%no%]';

    $output[] = evo()->parseText($tpl, $ph);
}

evo()->setConfig('enable_filter', $enable_filter);
return implode("\n", $output);
