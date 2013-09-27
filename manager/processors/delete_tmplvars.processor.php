<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_template')) {	
	$e->setError(3);
	$e->dumpError();	
}
?>
<?php

	$id = isset($_GET['id'])? intval($_GET['id']):0;
	$forced = isset($_GET['force'])? $_GET['force']:0;

	// check for relations
	if(!$forced) {
		$sql="SELECT sc.id, sc.pagetitle,sc.description FROM $dbase.`".$table_prefix."site_content` sc INNER JOIN $dbase.`".$table_prefix."site_tmplvar_contentvalues` stcv ON stcv.contentid=sc.id WHERE stcv.tmplvarid=".$id.";";
		$drs = $modx->db->query($sql);
		$count = $modx->db->getRecordCount($drs);
		if($count>0){
			include_once "header.inc.php";
		?>	
			<script>
				function deletedocument() {
					document.location.href="index.php?id=<?php echo $id;?>&a=303&force=1";
				}
			</script>
			<h1><?php echo $_lang['tmplvars']; ?></h1>

	<div id="actions">
		<ul class="actionButtons">
			<li id="cmdDelete"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete"] ?>" /> <?php echo $_lang["delete"]; ?></a></td>
			<li id="cmdCancel"><a href="index.php?a=301&id=<?php echo $id;?>"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang["cancel"]; ?></a></li>
		</ul>
	</div>

			<div class="sectionHeader"><?php echo $_lang['tmplvars']; ?></div>
			<div class="sectionBody">
		<?php
			echo "<p>".$_lang['tmplvar_inuse']."</p>";
			echo "<ul>";
			for($i=0;$i<$count;$i++) {
				$row = $modx->db->getRow($drs);
				echo '<li><span style="width: 200px"><a href="index.php?id='.$row['id'].'&a=27">'.$row['pagetitle'].'</a></span>'.($row['description']!='' ? ' - '.$row['description'] : '').'</li>';
			}
			echo "</ul>";
			echo '</div>';		
			include_once "footer.inc.php";
			exit;
		}	
	}

	// invoke OnBeforeTVFormDelete event
	$modx->invokeEvent("OnBeforeTVFormDelete",
							array(
								"id"	=> $id
							));
						
	// delete variable
	$sql = "DELETE FROM $dbase.`".$table_prefix."site_tmplvars` WHERE id=".$id.";";
	$rs = $modx->db->query($sql);
	if(!$rs) {
		echo "Something went wrong while trying to delete the field...";
		exit;
	} else {		
		$header="Location: index.php?a=76&r=2";
		header($header);
	}

	// delete variable's content values
	$modx->db->query("DELETE FROM $dbase.`".$table_prefix."site_tmplvar_contentvalues` WHERE tmplvarid=".$id.";");
	
	// delete variable's template access
	$modx->db->query("DELETE FROM $dbase.`".$table_prefix."site_tmplvar_templates` WHERE tmplvarid=".$id.";");
	
	// delete variable's access permissions
	$modx->db->query("DELETE FROM $dbase.`".$table_prefix."site_tmplvar_access` WHERE tmplvarid=".$id.";");

	// invoke OnTVFormDelete event
	$modx->invokeEvent("OnTVFormDelete",
							array(
								"id"	=> $id
							));								
?>