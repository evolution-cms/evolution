<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_document')) {
    $e->setError(3);
    $e->dumpError();
}

if(isset($_REQUEST['id'])) {
    $id = intval($_REQUEST['id']);
} else {
    $e->setError(2);
    $e->dumpError();
}

// check permissions on the document
include_once "./processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
    ?><br /><br /><div class="sectionHeader"><?php echo $_lang['access_permissions']; ?></div><div class="sectionBody">
    <p><?php echo $_lang['access_permission_denied']; ?></p>
    <?php
    include("footer.inc.php");
    exit;
}
?>

<script language="javascript">
parent.tree.ca = "move";

function setMoveValue(pId, pName) {
    if (pId==0 || checkParentChildRelation(pId, pName)) {
        document.newdocumentparent.new_parent.value=pId;
        document.getElementById('parentName').innerHTML = "<?php echo $_lang['new_parent']; ?>: <b>" + pId + "</b> (" + pName + ")";
    }
}

// check if the selected parent is a child of this document
function checkParentChildRelation(pId, pName) {
    var sp;
    var id = document.newdocumentparent.id.value;
    var tdoc = parent.tree.document;
    var pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pId) : tdoc.all["node"+pId];
    if (!pn) return;
    if (pn.id.substr(4)==id) {
        alert("<?php echo $_lang['illegal_parent_self']; ?>");
        return;
    }
    else {
        while (pn.p>0) {
            pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pn.p) : tdoc.all["node"+pn.p];
            if (pn.id.substr(4)==id) {
                alert("<?php echo $_lang['illegal_parent_child']; ?>");
                return;
            }
        }
    }
    return true;
}

</script>



<h1><?php echo $_lang['move_resource_title']; ?></h1>

<div id="actions">
	<ul class="actionButtons">
	    <li><a href="#" onclick="document.newdocumentparent.submit();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['save'] ?></a></li>
	  <li><a href="#" onclick="documentDirty=false;<?php echo $id==0 ? "document.location.href='index.php?a=2';" : "document.location.href='index.php?a=3&amp;id=$id';"?>"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']?></a></li>
	</ul>
</div>

<div class="section">
<div class="sectionHeader"><?php echo $_lang['move_resource_title']; ?></div><div class="sectionBody">
<?php echo $_lang['move_resource_message']; ?><p />
<form method="post" action="index.php" name='newdocumentparent'>
<input type="hidden" name="a" value="52">
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="idshow" value="<?php echo $id; ?>"><?php echo $_lang['resource_to_be_moved']; ?>: <b><?php echo $id; ?></b><br />
<span id="parentName" class="warning"><?php echo $_lang['move_resource_new_parent']; ?></span><br />
<input type="hidden" name="new_parent" value="" class="inputBox">
<br />
<input type='save' value="Move" style="display:none">
</form>
</div>
</div>
