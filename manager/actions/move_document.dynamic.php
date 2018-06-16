<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_document')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

if (isset($_REQUEST['id'])) {
    $id = (int)$_REQUEST['id'];
} else {
    $modx->webAlertAndQuit($_lang["error_no_id"]);
}

// check permissions on the document
include_once MODX_MANAGER_PATH . "processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if (!$udperms->checkPermissions()) {
    $modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

// Set the item name for logger
$pagetitle = $modx->getDatabase()->getValue($modx->getDatabase()->select('pagetitle', $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'"));
$_SESSION['itemname'] = $pagetitle;

?>
<script language="javascript">

  parent.tree.ca = 'move';

  var actions = {
    save: function() {
      documentDirty = false;
      document.newdocumentparent.submit();
    },
    cancel: function() {
      documentDirty = false;
        <?= ($id == 0 ? 'document.location.href="index.php?a=2";' : 'document.location.href="index.php?a=3&id=' . $id . '";') ?>
    }
  };

  function setMoveValue(pId, pName)
  {
    if (pId === 0 || checkParentChildRelation(pId, pName)) {
      documentDirty = true;
      document.newdocumentparent.new_parent.value = pId;
      document.getElementById('parentName').innerHTML = '<?= $_lang['new_parent'] ?>: <span class="text-primary"><b>' + pId + '</b> (' + pName + ')</span>';
    }
  }

  // check if the selected parent is a child of this document
  function checkParentChildRelation(pId, pName)
  {
    var sp;
    var id = document.newdocumentparent.id.value;
    var tdoc = parent.tree.document;
    var pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pId) : tdoc.all['node' + pId];
    if (!pn) {
      return;
    }
    if (pn.id.substr(4) === id) {
      alert('<?= $_lang['illegal_parent_self'] ?>');
      return;
    } else {
      while (pn.p > 0) {
        pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pn.p) : tdoc.all['node' + pn.p];
        if (pn.id.substr(4) === id) {
          alert('<?= $_lang['illegal_parent_child'] ?>');
          return;
        }
      }
    }
    return true;
  }

</script>

<h1>
    <i class="fa fa-arrows"></i><?= ($pagetitle ? $pagetitle . '<small>(' . $id . ')</small>' : $_lang['move_resource_title']) ?>
</h1>

<?= $_style['actionbuttons']['dynamic']['save'] ?>

<div class="tab-page">
    <div class="container container-body">
        <p class="alert alert-info"><?= $_lang['move_resource_message'] ?></p>
        <form method="post" action="index.php" name="newdocumentparent">
            <input type="hidden" name="a" value="52" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <input type="hidden" name="idshow" value="<?= $id ?>" />
            <input type="hidden" name="new_parent" value="" />
            <p><?= $_lang['resource_to_be_moved'] ?>: <b><?= $id ?></b></p>
            <span id="parentName"><?= $_lang['move_resource_new_parent'] ?></span>
        </form>
    </div>
</div>
