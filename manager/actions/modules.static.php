<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

if(!($modx->hasPermission('new_module')||$modx->hasPermission('edit_module')||$modx->hasPermission('exec_module'))) {
	$e->setError(3);
	$e->dumpError();
}
$theme = $manager_theme ? "$manager_theme/":"";

// initialize page view state - the $_PAGE object
$modx->manager->initPageViewState();

// get and save search string
if($_REQUEST['op']=='reset') {
	$query = '';
	$_PAGE['vs']['search']='';
}
else {
	$query = isset($_REQUEST['search'])? $_REQUEST['search']:$_PAGE['vs']['search'];
	$sqlQuery = $modx->db->escape($query);
	$_PAGE['vs']['search'] = $query;
}

// get & save listmode
$listmode = isset($_REQUEST['listmode']) ? $_REQUEST['listmode']:$_PAGE['vs']['lm'];
$_PAGE['vs']['lm'] = $listmode;


// context menu
include_once MODX_MANAGER_PATH."includes/controls/contextmenu.php";
$cm = new ContextMenu("cntxm", 150);
$cm->addItem($_lang["run_module"],"js:menuAction(1)",$_style['icons_save'],(!$modx->hasPermission('exec_module') ? 1:0));
$cm->addSeparator();
$cm->addItem($_lang["edit"],"js:menuAction(2)",$_style['icons_edit_document'],(!$modx->hasPermission('edit_module') ? 1:0));
$cm->addItem($_lang["duplicate"],"js:menuAction(3)",$_style['icons_resource_duplicate'],(!$modx->hasPermission('new_module') ? 1:0));
$cm->addItem($_lang["delete"], "js:menuAction(4)",$_style['icons_delete'],(!$modx->hasPermission('delete_module') ? 1:0));
echo $cm->render();

?>
<script type="text/javascript">
	var selectedItem;
	var contextm = <?php echo $cm->getClientScriptObject(); ?>;
	function showContentMenu(id,e){
		selectedItem=id;
		contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft)))<?php echo $modx_textdir ? '-190' : '';?>+"px"; //offset menu if RTL is selected
		contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop)))+"px";
		contextm.style.visibility = "visible";
		e.cancelBubble=true;
		return false;
	};

	function menuAction(a) {
		var id = selectedItem;
		switch(a) {
			case 1:		// run module
				dontShowWorker = true; // prevent worker from being displayed
				window.location.href='index.php?a=112&id='+id;
				break;
			case 2:		// edit
				window.location.href='index.php?a=108&id='+id;
				break;
			case 3:		// duplicate
				if(confirm("<?php echo $_lang['confirm_duplicate_record'] ?>")==true) {
					window.location.href='index.php?a=111&id='+id;
				}
				break;
			case 4:		// delete
				if(confirm("<?php echo $_lang['confirm_delete_module']; ?>")==true) {
					window.location.href='index.php?a=110&id='+id;
				}
				break;
		}
	}

	document.addEvent('click', function(){
		contextm.style.visibility = "hidden";
	});
</script>

<h1><?php echo $_lang['module_management']; ?></h1>
<div class="section">
<div class="sectionBody">
	<!-- load modules -->
	<p><?php echo $_lang['module_management_msg']; ?></p>

	<div id="actions">
		<ul class="actionButtons">
			<?php if(($modx->hasPermission('new_module'))){ echo '<li id="newModule"><a href="index.php?a=107"><img src="'. $_style["icons_save"]. '" />'. $_lang["new_module"].'</a></li>'; } ?>
		</ul>
	</div>

	<div>
	<?php

	$sql = "SELECT id,name,description,IF(locked,'Yes','-') as 'locked',IF(disabled,'".$_lang['yes']."','-') as 'disabled',IF(icon<>'',icon,'".$_style['icons_modules']."') as'icon' " .
			"FROM ".$modx->getFullTableName("site_modules")." ".
			(!empty($sqlQuery) ? " WHERE (name LIKE '%$sqlQuery%') OR (description LIKE '%$sqlQuery%')":"")." ".
			"ORDER BY name";
	$ds = $modx->db->query($sql);
	include_once MODX_MANAGER_PATH."includes/controls/datagrid.class.php";
	$grd = new DataGrid('',$ds,$number_of_results); // set page size to 0 t show all items
	$grd->noRecordMsg = $_lang["no_records_found"];
	$grd->cssClass="grid";
	$grd->columnHeaderClass="gridHeader";
	$grd->itemClass="gridItem";
	$grd->altItemClass="gridAltItem";
	$grd->fields="icon,name,description,locked,disabled";
	$grd->columns=$_lang["icon"]." ,".$_lang["name"]." ,".$_lang["description"]." ,".$_lang["locked"]." ,".$_lang["disabled"];
	$grd->colWidths="34,,,60,60";
	$grd->colAligns="center,,,center,center";
	$grd->colTypes="template:<a class='gridRowIcon' href='#' onclick='return showContentMenu([+id+],event);' title='".$_lang["click_to_context"]."'><img src='[+value+]' width='32' height='32' /></a>||template:<a href='index.php?a=108&id=[+id+]' title='".$_lang["module_edit_click_title"]."'>[+value+]</a>";
	if($listmode=='1') $grd->pageSize=0;
	if($_REQUEST['op']=='reset') $grd->pageNumber = 1;
	// render grid
	echo $grd->render();
	?>
	</div>
</div>
</div>
