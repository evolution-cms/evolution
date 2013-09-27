 <?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

switch((int) $_REQUEST['a']) {
  case 16:
    if(!$modx->hasPermission('edit_template')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  case 19:
    if(!$modx->hasPermission('new_template')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  default:
    $e->setError(3);
    $e->dumpError();
}

if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
    $id = (int) $_REQUEST['id'];
    // check to see the template editor isn't locked
    $rs = $modx->db->select('internalKey, username','[+prefix+]active_users',"action=16 AND id='{$id}'");
    $limit = $modx->db->getRecordCount($rs);
    if($limit>1) {
        for ($i=0;$i<$limit;$i++) {
            $lock = $modx->db->getRow($rs);
            if($lock['internalKey']!=$modx->getLoginUserID()) {
                $msg = sprintf($_lang["lock_msg"],$lock['username'],"template");
                $e->setError(5, $msg);
                $e->dumpError();
            }
        }
    }
    // end check for lock
} else {
    $id='';
}

$content = array();
if(!empty($id)) {
    $rs = $modx->db->select('*','[+prefix+]site_templates',"id='{$id}'");
    $limit = $modx->db->getRecordCount($rs);
    if($limit>1) {
        echo "Oops, something went terribly wrong...<p>";
        print "More results returned than expected. Which sucks. <p>Aborting.";
        exit;
    }
    if($limit<1) {
        echo "Oops, something went terribly wrong...<p>";
        print "No database record has been found for this template. <p>Aborting.";
        exit;
    }
    $content = $modx->db->getRow($rs);
    $_SESSION['itemname']=$content['templatename'];
    if($content['locked']==1 && $_SESSION['mgrRole']!=1) {
        $e->setError(3);
        $e->dumpError();
    }
} else {
    $_SESSION['itemname']="New template";
}

$content = array_merge($content, $_POST);

?>
<script type="text/javascript">
function duplicaterecord(){
    if(confirm("<?php echo $_lang['confirm_duplicate_record'] ?>")==true) {
        documentDirty=false;
        document.location.href="index.php?id=<?php echo $_REQUEST['id']; ?>&a=96";
    }
}

function deletedocument() {
    if(confirm("<?php echo $_lang['confirm_delete_template']; ?>")==true) {
        documentDirty=false;
        document.location.href="index.php?id=" + document.mutate.id.value + "&a=21";
    }
}

</script>

<form name="mutate" method="post" action="index.php">
<?php
    // invoke OnTempFormPrerender event
    $evtOut = $modx->invokeEvent("OnTempFormPrerender",array("id" => $id));
    if(is_array($evtOut)) echo implode("",$evtOut);
?>
<input type="hidden" name="a" value="20">
<input type="hidden" name="id" value="<?php echo $_REQUEST['id'];?>">
<input type="hidden" name="mode" value="<?php echo (int) $_REQUEST['a'];?>">

    <h1><?php echo $_lang['template_title']; ?></h1>

    <div id="actions">
          <ul class="actionButtons">
              <li id="Button1">
                <a href="#" onclick="documentDirty=false; document.mutate.save.click();saveWait('mutate');">
                  <img src="<?php echo $_style["icons_save"]?>" /> <?php echo $_lang['save']?>
                </a>
                  <span class="and"> + </span>
                <select id="stay" name="stay">
                  <option id="stay1" value="1" <?php echo $_REQUEST['stay']=='1' ? ' selected="selected"' : ''?> ><?php echo $_lang['stay_new']?></option>
                  <option id="stay2" value="2" <?php echo $_REQUEST['stay']=='2' ? ' selected="selected"' : ''?> ><?php echo $_lang['stay']?></option>
                  <option id="stay3" value=""  <?php echo $_REQUEST['stay']=='' ? ' selected="selected"' : ''?>  ><?php echo $_lang['close']?></option>
                </select>
              </li>
              <?php
                if ($_REQUEST['a'] == '16') { ?>
              <li id="Button2"><a href="#" onclick="duplicaterecord();"><img src="<?php echo $_style["icons_resource_duplicate"] ?>" /> <?php echo $_lang["duplicate"]; ?></a></li>
              <li id="Button3" class="disabled"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete_document"]?>" /> <?php echo $_lang['delete']?></a></li>
              <?php } else { ?>
              <li id="Button3"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete_document"]?>" /> <?php echo $_lang['delete']?></a></li>
              <?php } ?>
              <li id="Button5"><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=76';"><img src="<?php echo $_style["icons_cancel"]?>" /> <?php echo $_lang['cancel']?></a></li>
          </ul>
    </div>

<script type="text/javascript" src="media/script/tabpane.js"></script>
<div class="sectionBody">
<div class="tab-pane" id="templatesPane">
    <script type="text/javascript">
        tp = new WebFXTabPane( document.getElementById( "templatesPane" ), <?php echo $modx->config['remember_last_tab'] == 1 ? 'true' : 'false'; ?> );
    </script>
    <div class="tab-page" id="tabTemplate">
        <h2 class="tab"><?php echo $_lang["template_edit_tab"] ?></h2>
        <script type="text/javascript">tp.addTabPage( document.getElementById( "tabTemplate" ) );</script>

<?php echo '<div>' . $_lang['template_msg'] . '</div>'; ?>
    <table>
      <tr>
        <th><?php echo $_lang['template_name']; ?>:</th>
        <td><input name="templatename" type="text" maxlength="100" value="<?php echo htmlspecialchars($content['templatename']);?>" class="inputBox" style="width:300px;" onchange="documentDirty=true;"><span class="warning" id='savingMessage'></span></td>
      </tr>
    <tr>
    <th><?php echo $_lang['template_desc']; ?>:</th>
    <td><input name="description" type="text" maxlength="255" value="<?php echo htmlspecialchars($content['description']);?>" class="inputBox" style="width:300px;" onchange="documentDirty=true;"></td>
    </tr>
    <tr>
    <th><?php echo $_lang['existing_category']; ?>:</th>
    <td><select name="categoryid" style="width:300px;" onchange="documentDirty=true;">
            <option>&nbsp;</option>
            <?php
                include_once "categories.inc.php";
                $ds = getCategories();
                if($ds) foreach($ds as $n=>$v){
                    echo "<option value='".$v['id']."'".($content["category"]==$v["id"]? " selected='selected'":"").">".htmlspecialchars($v["category"])."</option>";
                }
            ?>
        </select>
    </td>
    </tr>
    <tr>
    <th><?php echo $_lang['new_category']; ?>:</th>
    <td><input name="newcategory" type="text" maxlength="45" value="<?php echo isset($content['newcategory']) ? $content['newcategory'] : '' ?>" class="inputBox" style="width:300px;" onchange="documentDirty=true;"></td>
    </tr>
<?php if($modx->hasPermission('save_role')):?>
    <tr>
    <td colspan="2"><label style="display:block;"><input name="locked" type="checkbox" <?php echo $content['locked']==1 ? "checked='checked'" : "" ;?> class="inputBox"> <?php echo $_lang['lock_template']; ?></label> <span class="comment"><?php echo $_lang['lock_template_msg']; ?></span></td>
    </tr>
<?php endif;?>
    </table>
    <!-- HTML text editor start -->
    <div class="section">
        <div class="sectionHeader">
            <?php echo $_lang['template_code']; ?>
        </div>
        <div class="sectionBody">
        <textarea dir="ltr" name="post" class="phptextarea" style="width:100%; height: 370px;" onChange="documentDirty=true;"><?php echo isset($content['post']) ? htmlspecialchars($content['post']) : htmlspecialchars($content['content']); ?></textarea>
        </div>
    </div>
    <!-- HTML text editor end -->
    <input type="submit" name="save" style="display:none">

<?php
$sql = "SELECT tv.name as 'name', tv.id as 'id', tr.templateid, tr.rank, if(isnull(cat.category),'".$_lang['no_category']."',cat.category) as category
    FROM ".$modx->getFullTableName('site_tmplvar_templates')." tr
    INNER JOIN ".$modx->getFullTableName('site_tmplvars')." tv ON tv.id = tr.tmplvarid
    LEFT JOIN ".$modx->getFullTableName('categories')." cat ON tv.category = cat.id
    WHERE tr.templateid='{$id}' ORDER BY tr.rank, tv.rank, tv.id";


$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
?>
    </div>
    <div class="tab-page" id="tabAssignedTVs">
        <h2 class="tab"><?php echo $_lang["template_assignedtv_tab"] ?></h2>
        <script type="text/javascript">tp.addTabPage( document.getElementById( "tabAssignedTVs" ) );</script>
        <p><?php if ($limit > 0) echo $_lang['template_tv_msg']; ?></p>
        <p><?php if($modx->hasPermission('save_template') && $limit > 1) { ?><a href="index.php?a=117&amp;id=<?php echo $_REQUEST['id'] ?>"><?php echo $_lang['template_tv_edit']; ?></a><?php } ?></p>
<?php
$tvList = '';

if($limit>0) {
    for ($i=0;$i<$limit;$i++) {
        $row = $modx->db->getRow($rs);
        if ($i == 0 ) $tvList .= '<br /><ul>';
        $tvList .= '<li><strong>'.$row['name'].'</strong> ('.$row['category'].')</li>';
    }
    $tvList .= '</ul>';

} else {
	echo $_lang['template_no_tv'];
}
echo $tvList;
?></div>
<?php
// invoke OnTempFormRender event
$evtOut = $modx->invokeEvent("OnTempFormRender",array("id" => $id));
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</div>
</div>
</form>
