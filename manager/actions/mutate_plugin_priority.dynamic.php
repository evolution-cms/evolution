<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_plugin')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$siteURL = $modx->config['site_url'];

$updateMsg = '';

if (isset($_POST['listSubmitted'])) {
    $updateMsg .= '<span class="text-success" id="updated">' . $_lang['sort_updated'] . '</span>';
    $tbl = $modx->getDatabase()->getFullTableName('site_plugin_events');

    foreach ($_POST as $listName => $listValue) {
        if ($listName == 'listSubmitted') {
            continue;
        }
        $orderArray = explode(',', $listValue);
        $listName = ltrim($listName, 'list_');
        if (count($orderArray) > 0) {
            foreach ($orderArray as $key => $item) {
                if ($item == '') {
                    continue;
                }
                $pluginId = ltrim($item, 'item_');
                $modx->getDatabase()->update(array('priority' => $key), $tbl, "pluginid='{$pluginId}' AND evtid='{$listName}'");
            }
        }
    }
    // empty cache
    $modx->clearCache('full');
}

$rs = $modx->getDatabase()->select("sysevt.name as evtname, sysevt.id as evtid, pe.pluginid, plugs.name, pe.priority, plugs.disabled", $modx->getDatabase()->getFullTableName('system_eventnames') . " sysevt
		INNER JOIN " . $modx->getDatabase()->getFullTableName('site_plugin_events') . " pe ON pe.evtid = sysevt.id
		INNER JOIN " . $modx->getDatabase()->getFullTableName('site_plugins') . " plugs ON plugs.id = pe.pluginid", '', 'sysevt.name,pe.priority');

$insideUl = 0;
$preEvt = '';
$sortableList = '';
$sortables = array();

while ($plugins = $modx->getDatabase()->getRow($rs)) {
    if ($preEvt !== $plugins['evtid']) {
        $sortables[] = $plugins['evtid'];
        $sortableList .= $insideUl ? '</ul></div>' : '';
        $sortableList .= '<div class="form-group clearfix"><strong>' . $plugins['evtname'] . '</strong><ul id="' . $plugins['evtid'] . '" class="sortableList">';
        $insideUl = 1;
    }
    $sortableList .= '<li id="item_' . $plugins['pluginid'] . '"' . ($plugins['disabled'] ? ' class="disabledPlugin"' : '') . '><i class="fa fa-plug"></i> ' . $plugins['name'] . ($plugins['disabled'] ? ' (hide)' : '') . '</li>';
    $preEvt = $plugins['evtid'];
}
if ($insideUl) {
    $sortableList .= '</ul></div>';
}

require_once(MODX_MANAGER_PATH . 'includes/header.inc.php');
?>

<script type="text/javascript">

    var actions = {
        save: function() {
            var el = document.getElementById('updated');
            if (el) {
                el.style.display = 'none';
            }
            el = document.getElementById('updating');
            if (el) {
                el.style.display = 'block';
            }
            setTimeout('document.sortableListForm.submit()', 1000);
        }, cancel: function() {
            window.location.href = 'index.php?a=76';
        },
    };

</script>

<h1>
    <i class="fa fa-sort-numeric-asc"></i><?= $_lang['plugin_priority_title'] ?>
</h1>

<?= $_style['actionbuttons']['dynamic']['save'] ?>

<div class="tab-page">
    <div class="container container-body">
        <b><?= $_lang['plugin_priority'] ?></b>
        <p><?= $_lang['plugin_priority_instructions'] ?></p>

        <?= $updateMsg ?>

        <span class="text-danger" style="display:none;" id="updating"><?= $_lang['sort_updating'] ?></span>

        <?= $sortableList ?>
    </div>
</div>

<form action="" method="post" name="sortableListForm">
    <input type="hidden" name="listSubmitted" value="true" />
    <?php
    foreach ($sortables as $list) {
    ?>
    <input type="hidden" id="list_<?= $list ?>" name="list_<?= $list ?>" value="" />
    <?php
    }
    ?>
</form>

<script type="text/javascript">

    evo.sortable('.sortableList > li', {
        complete: function(a) {
            let list = [];
            for (let i = 0; i < a.parentNode.childNodes.length; i++) {
                list.push(a.parentNode.childNodes[i].id);
            }
            document.getElementById('list_' + a.parentNode.id).value = list.join(',');
        },
    });

</script>
