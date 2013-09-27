<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('manage_metatags')) {
	$e->setError(3);
	$e->dumpError();
}

// initialize page view state - the $_PAGE object
$modx->manager->initPageViewState();

?>
<script type="text/javascript">

	// meta tag rows
	var tagRows = []; // stores tag information in 2D array. 2nd array = 0-name,1-tag,2-value,3-http_equiv

	function checkForm() {
		var requireConfirm=false;
		var deleteList="";
	<?php 
		$sql = "SELECT * FROM $dbase.`".$table_prefix."site_keywords` ORDER BY keyword ASC";
		$rs = $modx->db->query($sql);
		$limit = $modx->db->getRecordCount($rs); 
		for($i=0;$i<$limit;$i++) {
		$row=$modx->db->getRow($rs);
		?>

		if(document.getElementById('delete<?php echo $row['id']; ?>').checked==true) {
			requireConfirm = true;
			deleteList = deleteList + "\n - <?php echo addslashes($row['keyword']); ?>";

		}
	<?php }	?>
		if(requireConfirm) {
			var agree=confirm("<?php echo $_lang['confirm_delete_keywords']; ?>\n" + deleteList);
			if(agree) {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	function addTag() {
		var f=document.metatag;
		if(!f) return;
		if(!f.tagname.value) alert("<?php echo $_lang["require_tagname"];?>");
		else if(!f.tagvalue.value) alert("<?php echo $_lang["require_tagvalue"];?>");
		else {
			f.op.value=(f.cmdsavetag.value=="<?php echo $_lang["save_tag"];?>") ? 'edttag':'addtag';
			f.submit();
		}
	}

	function editTag(id){
		var opt;
		var f=document.metatag;
		if(!f) return;
		f.tagname.value = tagRows[id][0];
		f.tagvalue.value= tagRows[id][2];
		for(i=0;i<f.tag.options.length;i++) {
			opt = f.tag.options[i];
			tagkey = tagRows[id][1]+";"+tagRows[id][3]; // combine tag and style to make key
			if(opt.value==tagkey){
				opt.selected = true;
				break;
			}
		}
		f.id.value=id;
		f.cmdsavetag.value='<?php echo $_lang["save_tag"];?>';
		f.cmdcanceltag.style.visibility = 'visible';
		f.tagname.focus();
	}

	function cancelTag(id){
		var opt;
		var f=document.metatag;
		if(!f) return;
		f.tagname.value = '';
		f.tagvalue.value= '';
		f.tag.options[0].selected = true;
		f.id.value='';
		f.cmdsavetag.value='<?php echo $_lang["add_tag"];?>';
		f.cmdcanceltag.style.visibility = 'hidden';
	}

	function deleteTag() {
		var f=document.metatag;
		if(!f) return;
		else if(confirm("<?php echo $_lang['confirm_delete_tags']; ?>")) {
			f.op.value='deltag';
			f.submit();
		}
	}

	</script>

<form name="metatag" method="post" action="index.php" onsubmit="return checkForm();">
<input type="hidden" name="a" value="82" />
<input type="hidden" name="op" value="82" />
<input type="hidden" name="id" value="" />
<br />
<!-- META tags -->
<div class="section">
<div class="sectionHeader"><?php echo $_lang['metatags'] ;?></div><div class="sectionBody">
	<?php echo $_lang['metatag_intro'] ;?><br /><br />
	<div class="searchbara">
	<table border="0" width="100%" cellspacing="1">
	  <tr>
		<td width="70%">
		<table border="0" cellspacing="1">
		  <tr>
			<td valign="bottom"><?php echo $_lang['name'];?><br />
			<input type="text" name="tagname" size="15"></td>
			<td valign="bottom"><?php echo $_lang['tag'];?><br />
			<select size="1" name="tag">
        		<optgroup label="Named Meta Content">
        			<option value="abstract;0">abstract</option>
        			<option value="author;0">author</option>
        			<option value="classification;0">classification</option>
        			<option value="copyright;0">copyright</option>
        			<option value="description;0">description</option>
        			<option value="designer;0">designer</option>
        			<option value="distribution;0">distribution</option>
        			<option value="expires;1">expires</option>
        			<option value="generator;0">generator</option>
        			<option value="googlebot;0">googlebot</option>
        			<option value="keywords;0">keywords</option>
        			<option value="MSSmartTagsPreventParsing;0">MSSmartTagsPreventParsing</option>
        			<option value="owner;0">owner</option>
        			<option value="rating;0">rating</option>
        			<option value="refresh;0">refresh</option>
        			<option value="reply-to;0">reply-to</option>
        			<option value="revisit-after;0">revisit-after</option>
        			<option value="robots;0">robots</option>
        			<option value="subject;0">subject</option>
        			<option value="title;0">title</option>
                </optgroup>
			    <optgroup label="HTTP-Header Equivalents">
        			<option value="content-language;1">content-language</option>
        			<option value="content-type;1">content-type</option>
        			<option value="expires;1">expires</option>
        			<option value="imagetoolbar;1">imagetoolbar</option>
        			<option value="pics-label;1">pics-label</option>
        			<option value="pragma;1">pragma</option>
        			<option value="refresh;1">refresh</option>
        			<option value="set-cookie;1">set-cookie</option>
        		</optgroup>
			</select></td>
			<td valign="bottom"><?php echo $_lang['value'];?><br />
			<input type="text" name="tagvalue" size="20"></td>
			<td nowrap="nowrap"><br />
			<input type="button" value="<?php echo $_lang["add_tag"];?>" name="cmdsavetag" onclick="addTag()" /> <input style="visibility:hidden" type="button" value="<?php echo $_lang["cancel"];?>" name="cmdcanceltag" onclick="cancelTag()" /></td>
		  </tr>
		  <tr>
		      <td colspan="4"><p><?php echo $_lang['metatag_notice'];?></p></td>
	      </tr>
		</table>
		</td>
	  </tr>
	</table>
	</div>
	<div>
	<?php

		$sql = "SELECT * " .
				"FROM ".$modx->getFullTableName("site_metatags")." st ".
				"ORDER BY name";
		$ds = $modx->db->query($sql);
		include_once MODX_MANAGER_PATH."includes/controls/datagrid.class.php";
		$grd = new DataGrid('',$ds,$number_of_results); // set page size to 0 t show all items
		$grd->noRecordMsg = $_lang["no_records_found"];
		$grd->cssClass="grid";
		$grd->columnHeaderClass="gridHeader";
		$grd->itemClass="gridItem";
		$grd->altItemClass="gridAltItem";
		$grd->fields="id,name,tag,tagvalue";
		$grd->columns=$_lang["delete"]." ,".$_lang["name"]." ,".$_lang["tag"]." ,".$_lang["value"];
		$grd->colWidths="40";
		$grd->colAligns="center";
		$grd->colTypes="template:<input name='tag[]' type='checkbox' value='[+id+]'/><img src='media/style/".$manager_theme."/images/icons/comment.gif' width='16' height='16' align='absmiddle' /></a>||".
					   "template:<a href='#' title='".$_lang["click_to_edit_title"]."' onclick='editTag([+id+])'>[+value+]</a><span style='display:none;'><script type=\"text/javascript\"> tagRows['[+id+]']=[\"[+name+]\",\"[+tag+]\",\"[+tagvalue+]\",\"[+http_equiv+]\"];</script>";
		echo $grd->render();
	?>
	</div>
	<table border=0 cellpadding=2 cellspacing=0>
		<tr><td colspan="5">&nbsp;</td></tr>
		<tr>
			<td align="right">
				<input type="button" name="cmddeltag" value="<?php echo $_lang["delete_tags"];?>" onclick="deleteTag();" />
			</td>
		</tr>
	</table>
</div>
</div>

<!-- keywords -->
<div class="section">
<div class="sectionHeader"><?php echo $_lang['keywords'] ;?></div><div class="sectionBody">
<?php echo $_lang['keywords_intro'] ;?><br /><br />
<?php
	$sql = "SELECT * FROM $dbase.`".$table_prefix."site_keywords` ORDER BY keyword ASC";
	$ds = $modx->db->query($sql);
	$grd = new DataGrid('',$ds,$number_of_results); // set page size to 0 t show all items
	$grd->noRecordMsg = $_lang["no_keywords_found"];
	$grd->cssClass="grid";
	$grd->columnHeaderClass="gridHeader";
	$grd->itemClass="gridItem";
	$grd->altItemClass="gridAltItem";
	$grd->fields="id,keyword,keyword";
	$grd->columns=$_lang["delete"]." ,".$_lang["keyword"]." ,".$_lang["rename"];
	$grd->colWidths="40";
	$grd->colAligns="center";
	$grd->colTypes="template:<input id=\"delete[+id+]\" name=\"delete_keywords[[+id+]]\" type=\"checkbox\"  />||".
				   "template:<a onclick=\"if(document.getElementById('delete[+id+]').checked==true) { document.getElementById('delete[+id+]').checked=false; } else { document.getElementById('delete[+id+]').checked=true; }; return false;\" style=\"cursor:pointer\">[+keyword+]</a>||".
				   "template:<input type=\"hidden\" name=\"orig_keywords[keyword[+id+]]\" value=\"[+keyword+]\" /><input type=\"text\" name=\"rename_keywords[keyword[+id+]]\" value=\"[+keyword+]\" style=\"width:100%;\" maxlength=\"40\" />";
	echo $grd->render();
?>
	<table border=0 cellpadding=2 cellspacing=0>
		<tr><td colspan="5">&nbsp;</td></tr>
		<tr>
			<td>
				<input type=submit value="<?php echo $_lang['save_all_changes']; ?>" onsubmit="return checkForm();" /> &nbsp;
			</td>
			<td align="right">&nbsp;<?php echo $_lang['new_keyword']; ?></td>
			<td>
				<input type="text" name="new_keyword" value="" size="30" maxlength="40" />
			</td>
		</tr>
	</table>
</div>
</div>
</form>

