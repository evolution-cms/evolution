<?php
$rs = $modx->getDatabase()->select('*', $modx->getDatabase()->getFullTableName('site_content'), '', 'editedon DESC', 10);

if ($modx->getDatabase()->getRecordCount($rs) < 1) {
    return '<tr><td>[%no_activity_message%]</td></tr>';
}
$tpl = '<tr>
    <td data-toggle="collapse" data-target=".collapse[+id+]" class="text-right"><span class="label label-info">[+id+]</span></td>
    <td data-toggle="collapse" data-target=".collapse[+id+]"><a class="[+status+]" title="[%edit_resource%]" href="index.php?a=3&amp;id=[+id+]" target="main">[+pagetitle+]</a></td>
    <td data-toggle="collapse" data-target=".collapse[+id+]" class="text-right text-nowrap">[+editedon:math("%s+[(server_offset_time)]"):dateFormat=`'.$modx->toDateFormat(0,'formatOnly').' %H:%M:%S`+]</td>
    <td data-toggle="collapse" data-target=".collapse[+id+]" class="text-nowrap">[+username+]</td>
    <td style="text-align: right;" class="actions">[+edit_btn+][+preview_btn+][+delete_btn+][+publish_btn+][+info_btn+]</td>
</tr>
<tr class="resource-overview-accordian collapse collapse[+id+]">
    <td colspan="6">
        <div class="overview-body text-small">
            <ul>
                <li><b>[%long_title%]</b>: [+longtitle+]</li>
                <li><b>[%description%]</b>: [+description+]</li>
                <li><b>[%resource_summary%]</b>: [+introtext+]</li>
                <li><b>[%type%]</b>: [+type:is(reference):then([%weblink%]):else([%resource%])+]</li>
                <li><b>[%resource_alias%]</b>: [+alias+]</li>
                <li><b>[%page_data_cacheable%]</b>: [+cacheable:is(1):then([%yes%]):else([%no%])+]</li>
                <li><b>[%resource_opt_show_menu%]</b>: [+hidemenu:is(0):then([%yes%]):else([%no%])+]</li>
                <li><b>[%page_data_template%]</b>: [+template:templatename+]</li>
            </ul>
        </div>
    </td>
</tr>';

$btntpl['edit'] = '<a title="[%edit_resource%]" href="index.php?a=27&amp;id=[+id+]" target="main"><i class="fa fa-edit fa-fw"></i></a> ';
$btntpl['preview_btn'] = '<a [+preview_disabled+]" title="[%preview_resource%]" target="_blank" href="../index.php?&amp;id=[+id+]"><i class="fa fa-eye fa-fw"></i></a> ';

$output = array();
while ($ph = $modx->getDatabase()->getRow($rs)) {
    $docid = $ph['id'];
    $_ = $modx->getUserInfo($ph['editedby']);
    $ph['username'] = $_['username'];

    if ($ph['deleted'] == 1) {
        $ph['status'] = 'deleted text-danger';
    } elseif ($ph['published'] == 0) {
        $ph['status'] = 'unpublished font-italic text-muted';
    } else {
        $ph['status'] = 'published';
    }

    if ($modx->hasPermission('edit_document')) {
        $ph['edit_btn'] = str_replace('[+id+]', $docid, $btntpl['edit']);
    } else {
        $ph['edit_btn'] = '';
    }

    $preview_disabled = ($ph['deleted'] == 1) ? 'disabled' : '';
    $ph['preview_btn'] = str_replace(array(
        '[+id+]',
        '[+preview_disabled+]'
    ), array(
        $docid,
        $preview_disabled
    ), $btntpl['preview_btn']);

    if ($modx->hasPermission('delete_document')) {
        if ($ph['deleted'] == 0) {
            $delete_btn = '<a onclick="return confirm(\'[%confirm_delete_record%]\')" title="[%delete_resource%]" href="index.php?a=6&amp;id=[+id+]" target="main"><i class="fa fa-trash fa-fw"></i></a> ';
        } else {
            $delete_btn = '<a onclick="return confirm(\'[%confirm_undelete%]\')" title="[%undelete_resource%]" href="index.php?a=63&amp;id=[+id+]" target="main"><i class="fa fa-arrow-circle-o-up fa-fw"></i></a> ';
        }
        $ph['delete_btn'] = str_replace('[+id+]', $docid, $delete_btn);
    } else {
        $ph['delete_btn'] = '';
    }

    if ($ph['deleted'] == 1 && $ph['published'] == 0) {
        $publish_btn = '<a class="disabled" title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main"><i class="fa fa-arrow-up fa-fw"></i></a> ';
    } elseif ($ph['deleted'] == 1 && $ph['published'] == 1) {
        $publish_btn = '<a class="disabled" title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main"><i class="fa fa-arrow-down fa-fw"></i></a> ';
    } elseif ($ph['deleted'] == 0 && $ph['published'] == 0) {
        $publish_btn = '<a title="[%publish_resource%]" href="index.php?a=61&amp;id=[+id+]" target="main"><i class="fa fa-arrow-up fa-fw"></i></a> ';
    } else {
        $publish_btn = '<a title="[%unpublish_resource%]" href="index.php?a=62&amp;id=[+id+]" target="main"><i class="fa fa-arrow-down fa-fw"></i></a> ';
    }
    $ph['publish_btn'] = str_replace('[+id+]', $docid, $publish_btn);

    $ph['info_btn'] = str_replace('[+id+]', $docid, '<a title="[%resource_overview%]" data-toggle="collapse" data-target=".collapse[+id+]"><i class="fa fa-info fa-fw"></i></a>');

    if ($ph['longtitle'] == '') {
        $ph['longtitle'] = '(<i>[%not_set%]</i>)';
    }
    if ($ph['description'] == '') {
        $ph['description'] = '(<i>[%not_set%]</i>)';
    }
    if ($ph['introtext'] == '') {
        $ph['introtext'] = '(<i>[%not_set%]</i>)';
    }
    if ($ph['alias'] == '') {
        $ph['alias'] = '(<i>[%not_set%]</i>)';
    }

    $output[] = $modx->parseText($tpl, $ph);
}

return implode("\n", $output);
