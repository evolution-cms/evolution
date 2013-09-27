<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

switch((int) $_REQUEST['a']) {
  case 102:
    if(!$modx->hasPermission('edit_plugin')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  case 101:
    if(!$modx->hasPermission('new_plugin')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  default:
    $e->setError(3);
    $e->dumpError();
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;


// check to see the plugin editor isn't locked
$rs = $modx->db->select('internalKey, username','[+prefix+]active_users',"action='102' AND id='{$id}'");
$limit = $modx->db->getRecordCount($rs);
if($limit>1)
{
    while($lock = $modx->db->getRow)
    {
        if($lock['internalKey']!=$modx->getLoginUserID())
        {
            $msg = sprintf($_lang["lock_msg"],$lock['username'],$_lang['plugin']);
            $e->setError(5, $msg);
            $e->dumpError();
        }
    }
}
// end check for lock

if(isset($_GET['id']))
{
    $rs = $modx->db->select('*','[+prefix+]site_plugins',"id='{$id}'");
    $limit = $modx->db->getRecordCount($rs);
    if($limit>1)
    {
        echo "Multiple plugins sharing same unique id. Not good.<p>";
        exit;
    }
    if($limit<1)
    {
        header("Location: {$modx->config['site_url']}");
    }
    $content = $modx->db->getRow($rs);
    $_SESSION['itemname']=$content['name'];
    if($content['locked']==1 && $modx->hasPermission('save_role')!=1)
    {
        $e->setError(3);
        $e->dumpError();
    }
}
else
{
    $_SESSION['itemname']='New Plugin';
}
?>
<script language="JavaScript">

function duplicaterecord(){
    if(confirm("<?php echo $_lang['confirm_duplicate_record'] ?>")==true) {
        documentDirty=false;
        document.location.href="index.php?id=<?php echo $_REQUEST['id']; ?>&a=105";
    }
}

function deletedocument() {
    if(confirm("<?php echo $_lang['confirm_delete_plugin']; ?>")==true) {
        documentDirty=false;
        document.location.href="index.php?id=" + document.mutate.id.value + "&a=104";
    }
}

function setTextWrap(ctrl,b){
    if(!ctrl) return;
    ctrl.wrap = (b)? "soft":"off";
}

// Current Params/Configurations
var currentParams = {};

function showParameters(ctrl) {
    var c,p,df,cp;
    var ar,desc,value,key,dt;

    currentParams = {}; // reset;

    if (ctrl) {
        f = ctrl.form;
    } else {
        f= document.forms['mutate'];
        if(!f) return;
    }

    // setup parameters
    tr = (document.getElementById) ? document.getElementById('displayparamrow'):document.all['displayparamrow'];
    dp = (f.properties.value) ? f.properties.value.split("&"):"";
    if(!dp) tr.style.display='none';
    else {
        t='<table width="300" style="margin-bottom:3px;margin-left:14px;background-color:#EEEEEE" cellpadding="2" cellspacing="1"><thead><tr><td width="50%"><?php echo $_lang['parameter']; ?></td><td width="50%"><?php echo $_lang['value']; ?></td></tr></thead>';
        for(p = 0; p < dp.length; p++) {
            dp[p]=(dp[p]+'').replace(/^\s|\s$/,""); // trim
            ar = dp[p].split("=");
            key = ar[0];        // param
            ar = (ar[1]+'').split(";");
            desc = ar[0];	// description
            dt = ar[1];		// data type
            value = decode((ar[2])? ar[2]:'');

            // store values for later retrieval
            if (key && (dt=='list' || dt=='list-multi')) currentParams[key] = [desc,dt,value,ar[3]];
            else if (key) currentParams[key] = [desc,dt,value];

            if (dt) {
                switch(dt) {
                case 'int':
                    c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" />';
                    break;
                case 'menu':
                    value = ar[3];
                    c = '<select name="prop_'+key+'" style="width:168px" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">';
                    ls = (ar[2]+'').split(",");
                    if(currentParams[key]==ar[2]) currentParams[key] = ls[0]; // use first list item as default
                    for(i=0;i<ls.length;i++){
                        c += '<option value="'+ls[i]+'"'+((ls[i]==value)? ' selected="selected"':'')+'>'+ls[i]+'</option>';
                    }
                    c += '</select>';
                    break;
                case 'list':
                    value = ar[3];
                    ls = (ar[2]+'').split(",");
                    if(currentParams[key]==ar[2]) currentParams[key] = ls[0]; // use first list item as default
                    c = '<select name="prop_'+key+'" size="'+ls.length+'" style="width:168px" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">';
                    for(i=0;i<ls.length;i++){
                        c += '<option value="'+ls[i]+'"'+((ls[i]==value)? ' selected="selected"':'')+'>'+ls[i]+'</option>';
                    }
                    c += '</select>';
                    break;
                case 'list-multi':
                    value = typeof ar[3] !== 'undefined' ? (ar[3]+'').replace(/^\s|\s$/,"") : '';
                    arrValue = value.split(",");
                    ls = (ar[2]+'').split(",");

                    if(currentParams[key]==ar[2]) currentParams[key] = ls[0]; // use first list item as default
                    c = '<select name="prop_'+key+'" size="'+ls.length+'" multiple="multiple" style="width:168px" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">';
                    for(i=0;i<ls.length;i++){
                        if(arrValue.length){
                            var found = false;
                            for(j=0;j<arrValue.length;j++){
                                if (ls[i] == arrValue[j]) {
                                    found = true;
                                }
                            }
                            if(found == true){
                                c += '<option value="'+ls[i]+'" selected="selected">'+ls[i]+'</option>';
                            }else{
                                c += '<option value="'+ls[i]+'">'+ls[i]+'</option>';
                            }
                        }else{
                            c += '<option value="'+ls[i]+'">'+ls[i]+'</option>';
                        }
                    }
                    c += '</select>';
                    break;
                case 'textarea':
                    c = '<textarea class="phptextarea" name="prop_'+key+'" cols="50" rows="4" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">'+value+'</textarea>';
                    break;
                default:  // string
                    c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" />';
                    break;

                }
                t +='<tr><td bgcolor="#FFFFFF" width="50%">'+desc+'</td><td bgcolor="#FFFFFF" width="50%">'+c+'</td></tr>';
            };
        }
        t+='</table>';
        td = (document.getElementById) ? document.getElementById('displayparams'):document.all['displayparams'];
        td.innerHTML = t;
        tr.style.display='';
    }
    implodeParameters();
}

function setParameter(key,dt,ctrl) {
    var v;
    if(!ctrl) return null;
    switch (dt) {
        case 'int':
            ctrl.value = parseInt(ctrl.value);
            if(isNaN(ctrl.value)) ctrl.value = 0;
            v = ctrl.value;
            break;
        case 'menu':
            v = ctrl.options[ctrl.selectedIndex].value;
            currentParams[key][3] = v;
            implodeParameters();
            return;
            break;
        case 'list':
            v = ctrl.options[ctrl.selectedIndex].value;
            currentParams[key][3] = v;
            implodeParameters();
            return;
            break;
        case 'list-multi':
            var arrValues = new Array;
            for(var i=0; i < ctrl.options.length; i++){
                if(ctrl.options[i].selected){
                    arrValues.push(ctrl.options[i].value);
                }
            }
            currentParams[key][3] = arrValues.toString();
            implodeParameters();
            return;
            break;
        default:
            v = ctrl.value+'';
            break;
    }
    currentParams[key][2] = v;
    implodeParameters();
}

// implode parameters
function implodeParameters(){
    var v, p, s='';
    for(p in currentParams){
        if(currentParams[p]) {
            v = currentParams[p].join(";");
            if(s && v) s+=' ';
            if(v) s += '&'+p+'='+ encode(v);
        }
    }
    document.forms['mutate'].properties.value = s;
}

function encode(s){
    s=s+'';
    s = s.replace(/\=/g,'%3D'); // =
    s = s.replace(/\&/g,'%26'); // &
    return s;
}

function decode(s){
    s=s+'';
    s = s.replace(/\%3D/g,'='); // =
    s = s.replace(/\%26/g,'&'); // &
    return s;
}

</script>

<form name="mutate" method="post" action="index.php?a=103" enctype="multipart/form-data">
<?php
// invoke OnPluginFormPrerender event
$evtOut = $modx->invokeEvent("OnPluginFormPrerender",array("id" => $id));
if(is_array($evtOut)) echo implode("",$evtOut);
?>
    <input type="hidden" name="id" value="<?php echo $content['id'];?>">
    <input type="hidden" name="mode" value="<?php echo $_GET['a'];?>">

    <h1><?php echo $_lang['plugin_title']; ?></h1>

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
                if ($_GET['a'] == '102') { ?>
              <li id="Button2"><a href="#" onclick="duplicaterecord();"><img src="<?php echo $_style["icons_resource_duplicate"] ?>" /> <?php echo $_lang["duplicate"]; ?></a></li>
              <li id="Button3" class="disabled"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete_document"] ?>" /> <?php echo $_lang['delete']?></a></li>
              <?php } else { ?>
              <li id="Button3"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete_document"] ?>" /> <?php echo $_lang['delete']?></a></li>
              <?php } ?>
              <li id="Button5"><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=76';"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']?></a></li>
          </ul>
    </div>

<div class="sectionBody">
<p><?php echo $_lang['plugin_msg']; ?></p>
<script type="text/javascript" src="media/script/tabpane.js"></script>
<div class="tab-pane" id="pluginPane">
    <script type="text/javascript">
        tp = new WebFXTabPane( document.getElementById( "pluginPane"), <?php echo $modx->config['remember_last_tab'] == 1 ? 'true' : 'false'; ?> );
    </script>

<!-- General -->
<div class="tab-page" id="tabPlugin">
    <h2 class="tab"><?php echo $_lang["settings_general"] ?></h2>
    <script type="text/javascript">tp.addTabPage( document.getElementById( "tabPlugin" ) );</script>
    <table>
      <tr>
        <th><?php echo $_lang['plugin_name']; ?>:</th>
        <td><input name="name" type="text" maxlength="100" value="<?php echo htmlspecialchars($content['name']);?>" class="inputBox" style="width:250px;" onchange="documentDirty=true;"><span class="warning" id="savingMessage">&nbsp;</span></td>
      </tr>
      <tr>
        <th><?php echo $_lang['plugin_desc']; ?>:&nbsp;&nbsp;</th>
        <td><input name="description" type="text" maxlength="255" value="<?php echo $content['description'];?>" class="inputBox" style="width:300px;" onchange="documentDirty=true;"></td>
      </tr>
      <tr>
        <th><?php echo $_lang['existing_category']; ?>:&nbsp;&nbsp;</th>
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
        <td><input name="newcategory" type="text" maxlength="45" value="" class="inputBox" style="width:300px;" onchange="documentDirty=true;"></td>
      </tr>
<?php if($modx->hasPermission('save_role')):?>
      <tr>
        <td valign="top" colspan="2"><label style="display:block;"><input name="locked" type="checkbox" <?php echo $content['locked']==1 ? "checked='checked'" : "" ;?> value="on" class="inputBox"> <?php echo $_lang['lock_plugin']; ?></label> <span class="comment"><?php echo $_lang['lock_plugin_msg']; ?></span></td>
      </tr>
<?php endif;?>
    </table>
    <!-- PHP text editor start -->
    <div class="section">
        <div class="sectionHeader">
            <span style="float:left;">&nbsp;<?php echo $_lang['plugin_code']; ?></span>
            <span style="float:right;"><?php echo $_lang['wrap_lines']; ?><input name="wrap" type="checkbox" <?php echo $content['wrap']== 1 ? "checked='checked'" : "" ;?> class="inputBox" onclick="setTextWrap(document.mutate.post,this.checked)" /></span>
        </div>
        <div class="sectionBody">
        <textarea dir="ltr" name="post" class="phptextarea" style="width:100%; height:370px;" wrap="<?php echo $content['wrap']== 1 ? "soft" : "off" ;?>" onchange="documentDirty=true;"><?php echo htmlspecialchars($content['plugincode']); ?></textarea>
        </div>
    </div>
    <!-- PHP text editor end -->
</div>

<!-- Configuration/Properties -->
<div class="tab-page" id="tabProps">
    <h2 class="tab"><?php echo $_lang["settings_config"] ?></h2>
    <script type="text/javascript">tp.addTabPage( document.getElementById( "tabProps" ) );</script>
        <table>
      <tr>
        <td valign="top" colspan="2"><label><input name="disabled" type="checkbox" <?php echo $content['disabled']==1 ? "checked='checked'" : "";?> value="on" class="inputBox"> <?php echo  $content['disabled']==1 ? "<span class='warning'>".$_lang['plugin_disabled']."</span></label>":$_lang['plugin_disabled']; ?></td>
      </tr>
          <tr>
            <th><?php echo $_lang['import_params']; ?>:&nbsp;&nbsp;</th>
            <td><select name="moduleguid" style="width:300px;" onchange="documentDirty=true;">
                <option>&nbsp;</option>
                <?php
                    $sql =    "SELECT sm.id,sm.name,sm.guid " .
                            "FROM ".$modx->getFullTableName("site_modules")." sm ".
                            "INNER JOIN ".$modx->getFullTableName("site_module_depobj")." smd ON smd.module=sm.id AND smd.type=30 ".
                            "INNER JOIN ".$modx->getFullTableName("site_plugins")." sp ON sp.id=smd.resource ".
                            "WHERE smd.resource='$id' AND sm.enable_sharedparams='1' ".
                            "ORDER BY sm.name ";
                    $ds = $modx->db->query($sql);
                    if($ds) while($row = $modx->db->getRow($ds)){
                        echo "<option value='".$row['guid']."'".($content["moduleguid"]==$row["guid"]? " selected='selected'":"").">".htmlspecialchars($row["name"])."</option>";
                    }
                ?>
            </select>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td valign="top"><span style="width:300px;" ><span class="comment"><?php echo $_lang['import_params_msg']; ?></span></span><br /><br /></td>
          </tr>
          <tr>
            <th valign="top"><?php echo $_lang['plugin_config']; ?>:</th>
            <td valign="top"><textarea class="phptextarea" name="properties" onChange='showParameters(this);documentDirty=true;'><?php echo $content['properties'];?></textarea><br /><input type="button" value="<?php echo $_lang['update_params']; ?>" /></td>
          </tr>
          <tr id="displayparamrow">
            <td valign="top">&nbsp;</td>
            <td id="displayparams">&nbsp;</td>
          </tr>
        </table>
        </div>

<!-- System Events -->
<div class="tab-page" id="tabEvents">
    <h2 class="tab"><?php echo $_lang["settings_events"] ?></h2>
    <script type="text/javascript">tp.addTabPage( document.getElementById( "tabEvents" ) );</script>
    <p><?php echo $_lang['plugin_event_msg']; ?></p>
    <table>
<?php

    // get selected events
    if(is_numeric($id) && $id > 0) {
        $evts = array();
        $rs = $modx->db->select('*','[+prefix+]site_plugin_events',"pluginid='{$id}'");
        $limit = $modx->db->getRecordCount($rs);
        for ($i=0; $i<$limit; $i++) {
            $row = $modx->db->getRow($rs);
            $evts[] = $row['evtid'];
        }
    } else {
        if(isset($content['sysevents']) && is_array($content['sysevents'])) {
            $evts = $content['sysevents'];
        } else {
            $evts = array();
        }
    }

    // display system events
    $evtnames = array();
    $services = array(
        "Parser Service Events",
        "Manager Access Events",
        "Web Access Service Events",
        "Cache Service Events",
        "Template Service Events",
        "User Defined Events"
    );
    $rs = $modx->db->select('*','[+prefix+]system_eventnames','','service DESC, groupname, name');
    $limit = $modx->db->getRecordCount($rs);
    if($limit==0) echo "<tr><td>&nbsp;</td></tr>";
    else for ($i=0; $i<$limit; $i++) {
        $row = $modx->db->getRow($rs);
        // display records
        if($srv!=$row['service']){
            $srv=$row['service'];
            if(count($evtnames)>0) echoEventRows($evtnames);
                echo "<tr><td colspan='2'><div class='split' style='margin:10px 0;'></div></td></tr>";
                echo "<tr><td colspan='2'><b>".$services[$srv-1]."</b></td></tr>";
        }
        // display group name
        if($grp!=$row['groupname']){
            $grp=$row['groupname'];
            if(count($evtnames)>0) echoEventRows($evtnames);
                echo "<tr><td colspan='2'><div class='split' style='margin:10px 0;'></div></td></tr>";
                echo "<tr><td colspan='2'><b>".$row['groupname']."</b></td></tr>";
        }
        $evtnames[] = '<input name="sysevents[]" id="' . $row['name'] . '" type="checkbox"'.(in_array($row['id'],$evts) ? " checked='checked' " : "").'class="inputBox" value="'.$row['id'].'" /><label for="'.$row['name']. '"' . bold(in_array($row[id],$evts)) . '>'.$row['name'].'</label>'."\n";
        if(count($evtnames)==2) echoEventRows($evtnames);
    }
    if(count($evtnames)>0) echoEventRows($evtnames);

    function echoEventRows(&$evtnames) {
        echo "<tr><td>".implode("</td><td>",$evtnames)."</td></tr>";
        $evtnames = array();
    }
?>
    </table>
            </td>
          </tr>
        </table>
</div>

</div>
<input type="submit" name="save" style="display:none">
</div>
<?php
// invoke OnPluginFormRender event
$evtOut = $modx->invokeEvent("OnPluginFormRender",array("id" => $id));
if(is_array($evtOut)) echo implode("",$evtOut);
?>
</form>
<script type="text/javascript">
setTimeout('showParameters()',10);
</script>
<?php
function bold($cond=false)
{
    if($cond!==false) return ' style="background-color:#777;color:#fff;"';
    else return;
}

