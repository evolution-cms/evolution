<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('settings')) {
	$e->setError(3);
	$e->dumpError();
}

// check to see the edit settings page isn't locked
$sql = "SELECT internalKey, username FROM $dbase.`".$table_prefix."active_users` WHERE $dbase.`".$table_prefix."active_users`.action=17";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit>1) {
	for ($i=0;$i<$limit;$i++) {
        $lock = $modx->db->getRow($rs);
		if($lock['internalKey']!=$modx->getLoginUserID()) {
			$msg = sprintf($_lang["lock_settings_msg"],$lock['username']);
			$e->setError(5, $msg);
			$e->dumpError();
		}
	}
}
// end check for lock

// reload system settings from the database.
// this will prevent user-defined settings from being saved as system setting
$settings = array();
$sql = "SELECT setting_name, setting_value FROM $dbase.`".$table_prefix."system_settings`";
$rs = $modx->db->query($sql);
$number_of_settings = $modx->db->getRecordCount($rs);
while ($row = $modx->db->getRow($rs)) $settings[$row['setting_name']] = $row['setting_value'];
$settings['filemanager_path'] = preg_replace('@^' . MODX_BASE_PATH . '@', '[(base_path)]', $settings['filemanager_path']);
$settings['rb_base_dir']      = preg_replace('@^' . MODX_BASE_PATH . '@', '[(base_path)]', $settings['rb_base_dir']);
extract($settings, EXTR_OVERWRITE);

$displayStyle = ($_SESSION['browser']==='modern') ? 'table-row' : 'block' ;

// load languages and keys
$lang_keys = array();
$dir = dir("includes/lang");
while ($file = $dir->read()) {
    if(strpos($file, ".inc.php")>0) {
        $endpos = strpos ($file, ".");
        $languagename = substr($file, 0, $endpos);
        $lang_keys[$languagename] = get_lang_keys($file);
    }
}
$dir->close();

$isDefaultUnavailableMsg = $site_unavailable_message == $_lang['siteunavailable_message_default'];
$isDefaultUnavailableMsgJs = $isDefaultUnavailableMsg ? 'true' : 'false';
$site_unavailable_message_view = isset($site_unavailable_message) ? $site_unavailable_message : $_lang['siteunavailable_message_default'];

/* check the file paths */
$settings['filemanager_path'] = $filemanager_path = trim($settings['filemanager_path']) == '' ? MODX_BASE_PATH : $settings['filemanager_path'];
$settings['rb_base_dir'] = $rb_base_dir = trim($settings['rb_base_dir']) == '' ? MODX_BASE_PATH.'assets/' : $settings['rb_base_dir'];
$settings['rb_base_url'] =  $rb_base_url = trim($settings['rb_base_url']) == '' ? 'assets/' : $settings['rb_base_url'];

?>
<style type="text/css">
	table th {text-align:left; vertical-align:top;}
</style>
<script type="text/javascript">
function checkIM() {
	im_on = document.settings.im_plugin[0].checked; // check if im_plugin is on
	if(im_on==true) {
		showHide(/imRow/, 1);
	}
};

function checkCustomIcons() {
	if(document.settings.editor_toolbar.selectedIndex!=3) {
		showHide(/custom/,0);
	}
};

function showHide(what, onoff){

	var all = document.getElementsByTagName( "*" );
	var l = all.length;
	var buttonRe = what;
	var id, el, stylevar;

	if(onoff==1) {
		stylevar = "<?php echo $displayStyle; ?>";
	} else {
		stylevar = "none";
	}

	for ( var i = 0; i < l; i++ ) {
		el = all[i]
		id = el.id;
		if ( id == "" ) continue;
		if (buttonRe.test(id)) {
			el.style.display = stylevar;
		}
	}
};

function addContentType(){
	var i,o,exists=false;
	var txt = document.settings.txt_custom_contenttype;
	var lst = document.settings.lst_custom_contenttype;
	for(i=0;i<lst.options.length;i++)
	{
		if(lst.options[i].value==txt.value) {
			exists=true;
			break;
		}
	}
	if (!exists) {
		o = new Option(txt.value,txt.value);
		lst.options[lst.options.length]= o;
		updateContentType();
	}
	txt.value='';
}
function removeContentType(){
	var i;
	var lst = document.settings.lst_custom_contenttype;
	for(i=0;i<lst.options.length;i++) {
		if(lst.options[i].selected) {
			lst.remove(i);
			break;
		}
	}
	updateContentType();
}
function updateContentType(){
	var i,o,ol=[];
	var lst = document.settings.lst_custom_contenttype;
	var ct = document.settings.custom_contenttype;
	while(lst.options.length) {
		ol[ol.length] = lst.options[0].value;
		lst.options[0]= null;
	}
	if(ol.sort) ol.sort();
	ct.value = ol.join(",");
	for(i=0;i<ol.length;i++) {
		o = new Option(ol[i],ol[i]);
		lst.options[lst.options.length]= o;
	}
	documentDirty = true;
}
/**
 * @param element el were language selection comes from
 * @param string lkey language key to look up
 * @param id elupd html element to update with results
 * @param string default_str default value of string for loaded manager language - allows some level of confirmation of change from default
 */
function confirmLangChange(el, lkey, elupd){
    lang_current = document.getElementById(elupd).value;
    lang_default = document.getElementById(lkey+'_hidden').value;
    changed = lang_current != lang_default;
    proceed = true;
    if(changed) {
        proceed = confirm('<?php echo $_lang['confirm_setting_language_change']; ?>');
    }
    if(proceed) {
        //document.getElementById(elupd).value = '';
        lang = el.options[el.selectedIndex].value;
        var myAjax = new Ajax('index.php?a=118', {
            method: 'post',
            data: 'action=get&lang='+lang+'&key='+lkey
        }).request();
        myAjax.addEvent('onComplete', function(resp){
            document.getElementById(elupd).value = resp;
        });
    }
}

</script>
<form name="settings" action="index.php?a=30" method="post">

	<h1><?php echo $_lang['settings_title']; ?></h1>

	<div id="actions">
		  <ul class="actionButtons">
			  <li id="Button1">
				<a href="#" onclick="documentDirty=false; document.settings.submit();">
					<img src="<?php echo $_style["icons_save"]?>" /> <?php echo $_lang['save']; ?>
				</a>
			  </li>
			  <li id="Button5">
				<a href="#" onclick="documentDirty=false;document.location.href='index.php?a=2';">
					<img src="<?php echo $_style["icons_cancel"]?>" /> <?php echo $_lang['cancel']; ?>
				</a>
			  </li>
		</ul>
	</div>

<div style="margin: 0 10px 0 20px">
    <input type="hidden" name="site_id" value="<?php echo $site_id; ?>" />
    <input type="hidden" name="settings_version" value="<?php echo $modx->getVersionData('version'); ?>" />
    <!-- this field is used to check site settings have been entered/ updated after install or upgrade -->
    <?php if(!isset($settings_version) || $settings_version!=$modx->getVersionData('version')) { ?>
    <div class='sectionBody'><p><?php echo $_lang['settings_after_install']; ?></p></div>
    <?php } ?>
    <script type="text/javascript" src="media/script/tabpane.js"></script>
    <div class="tab-pane" id="settingsPane">
      <script type="text/javascript">
		tpSettings = new WebFXTabPane( document.getElementById( "settingsPane" ), <?php echo $modx->config['remember_last_tab'] == 1 ? 'true' : 'false'; ?> );
	</script>

	<!-- Site Settings -->
      <div class="tab-page" id="tabPage2">
        <h2 class="tab"><?php echo $_lang["settings_site"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage2" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
            <tr>
              <td nowrap class="warning"><b><?php echo htmlspecialchars($_lang["sitename_title"]) ?></b></td>
              <td ><input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 200px;" name="site_name" value="<?php echo isset($site_name) ? $site_name : "My MODX Site" ; ?>" /></td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["sitename_message"] ?></td>
            </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["language_title"]?></b></td>
            <td> <select name="manager_language" size="1" class="inputBox" onchange="documentDirty=true;">
<?php echo get_lang_options(null, $manager_language);?>
              </select> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["language_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
		  <tr>
            <td nowrap class="warning"><b><?php echo $_lang["charset_title"]?></b></td>
            <td> <select name="modx_charset" size="1" class="inputBox" style="width:250px;" onchange="documentDirty=true;">
                <?php include "charsets.php"; ?>
              </select> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["charset_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["xhtml_urls_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type="radio" name="xhtml_urls" value="1" <?php echo $xhtml_urls=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="xhtml_urls" value="0" <?php echo ($xhtml_urls=='0' || !isset($xhtml_urls)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["xhtml_urls_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["sitestart_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='10' size='5' name="site_start" value="<?php echo isset($site_start) ? $site_start : 1 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["sitestart_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["errorpage_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='10' size='5' name="error_page" value="<?php echo isset($error_page) ? $error_page : 1 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["errorpage_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["unauthorizedpage_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='10' size='5' name="unauthorized_page" value="<?php echo isset($unauthorized_page) ? $unauthorized_page : 1 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["unauthorizedpage_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["sitestatus_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type="radio" name="site_status" value="1" <?php echo ($site_status=='1' || !isset($site_status)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["online"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="site_status" value="0" <?php echo $site_status=='0' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["offline"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["sitestatus_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["siteunavailable_page_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" name="site_unavailable_page" type="text" maxlength="10" size="5" value="<?php echo isset($site_unavailable_page) ? $site_unavailable_page : "" ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["siteunavailable_page_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["siteunavailable_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_site_unavailable" id="reload_site_unavailable_select" onchange="confirmLangChange(this, 'siteunavailable_message_default', 'site_unavailable_message_textarea');">
<?php echo get_lang_options('siteunavailable_message_default');?>
              </select>
            </td>
            <td> <textarea name="site_unavailable_message" id="site_unavailable_message_textarea" style="width:100%; height: 120px;"><?php echo $site_unavailable_message_view; ?></textarea>
                <input type="hidden" name="siteunavailable_message_default" id="siteunavailable_message_default_hidden" value="<?php echo addslashes($_lang['siteunavailable_message_default']);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang['siteunavailable_message'];?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>

          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["track_visitors_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type="radio" name="track_visitors" value="1" <?php echo ($track_visitors=='1' || !isset($track_visitors)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="track_visitors" value="0" <?php echo $track_visitors=='0' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["track_visitors_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["top_howmany_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' size="5" name="top_howmany" value="<?php echo isset($top_howmany) ? $top_howmany : 10 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["top_howmany_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
            <tr>
                <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaulttemplate_logic_title"];?></b></td>
                <td>
                    <p><?php echo $_lang["defaulttemplate_logic_general_message"];?></p>
                    <input onchange="documentDirty=true;" type="radio" name="auto_template_logic" value="system"<?php if($auto_template_logic == 'system') {echo " checked='checked'";}?>/> <?php echo $_lang["defaulttemplate_logic_system_message"]; ?><br />
                    <input onchange="documentDirty=true;" type="radio" name="auto_template_logic" value="parent"<?php if($auto_template_logic == 'parent') {echo " checked='checked'";}?>/> <?php echo $_lang["defaulttemplate_logic_parent_message"]; ?><br />
                    <input onchange="documentDirty=true;" type="radio" name="auto_template_logic" value="sibling"<?php if($auto_template_logic == 'sibling') {echo " checked='checked'";}?>/> <?php echo $_lang["defaulttemplate_logic_sibling_message"]; ?><br />
                </td>
            </tr>
            <tr>
                <td colspan="2"><div class='split'></div></td>
            </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaulttemplate_title"] ?></b></td>
            <td>
			<?php
				$sql = 'SELECT t.templatename, t.id, c.category FROM '.$table_prefix.'site_templates t LEFT JOIN '.$table_prefix.'categories c ON t.category = c.id ORDER BY c.category, t.templatename ASC';
                        $rs = $modx->db->query($sql);
			?>
			  <select name="default_template" class="inputBox" onchange="documentDirty=true;wrap=document.getElementById('template_reset_options_wrapper');if(this.options[this.selectedIndex].value != '<?php echo $default_template;?>'){wrap.style.display='block';}else{wrap.style.display='none';}" style="width:150px">
				<?php
				
				$currentCategory = '';
                                while ($row = $modx->db->getRow($rs)) {
					$thisCategory = $row['category'];
					if($thisCategory == null) {
						$thisCategory = $_lang["no_category"];
					}
					if($thisCategory != $currentCategory) {
						if($closeOptGroup) {
							echo "\t\t\t\t\t</optgroup>\n";							
						}
						echo "\t\t\t\t\t<optgroup label=\"$thisCategory\">\n";
						$closeOptGroup = true;
					} else {
						$closeOptGroup = false;
					}
					
					$selectedtext = $row['id'] == $default_template ? ' selected="selected"' : '';
					if ($selectedtext) {
						$oldTmpId = $row['id'];
						$oldTmpName = $row['templatename'];
					}
					
					echo "\t\t\t\t\t".'<option value="'.$row['id'].'"'.$selectedtext.'>'.$row['templatename']."</option>\n";
					$currentCategory = $thisCategory;
				}
				if($thisCategory != '') {
					echo "\t\t\t\t\t</optgroup>\n";							
				}
?>
 			 </select>
 			 	<br />
                <div id="template_reset_options_wrapper" style="display:none;">
    				<input onchange="documentDirty=true;" type="radio" name="reset_template" value="1" /> <?php echo $_lang["template_reset_all"]; ?><br />
	    			<input onchange="documentDirty=true;" type="radio" name="reset_template" value="2" /> <?php echo sprintf($_lang["template_reset_specific"],$oldTmpName); ?>
                </div>
				<input type="hidden" name="old_template" value="<?php echo $oldTmpId; ?>" />
			</td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["defaulttemplate_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaultpublish_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="publish_default" value="1" <?php echo $publish_default=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="publish_default" value="0" <?php echo ($publish_default=='0' || !isset($publish_default)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["defaultpublish_message"] ?></td>
          </tr>

          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaultcache_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="cache_default" value="1" <?php echo $cache_default=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="cache_default" value="0" <?php echo ($cache_default=='0' || !isset($cache_default)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["defaultcache_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaultsearch_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="search_default" value="1" <?php echo $search_default=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="search_default" value="0" <?php echo ($search_default=='0' || !isset($search_default)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["defaultsearch_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr> 
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["defaultmenuindex_title"] ?></b></td> 
            <td> <input onchange="documentDirty=true;" type="radio" name="auto_menuindex" value="1" <?php echo ($auto_menuindex=='1' || !isset($auto_menuindex)) ? 'checked="checked"' : "" ; ?> /> 
            <?php echo $_lang["yes"]?><br /> 
            <input onchange="documentDirty=true;" type="radio" name="auto_menuindex" value="0" <?php echo ($auto_menuindex=='0') ? 'checked="checked"' : "" ; ?> /> 
            <?php echo $_lang["no"]?> </td> 
          </tr>
          <tr> 
            <td width="200">&nbsp;</td> 
            <td class='comment'><?php echo $_lang["defaultmenuindex_message"] ?></td> 
          </tr>
          <tr> 
            <td colspan="2"><div class='split'></div></td> 
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["custom_contenttype_title"] ?></b></td>
            <td><input name="txt_custom_contenttype" type="text" maxlength="100" style="width: 200px;" value="" /> <input type="button" value="<?php echo $_lang["add"]; ?>" onclick='addContentType()' /><br />
            <table border="0" cellspacing="0" cellpadding="0"><tr><td valign="top">
            <select name="lst_custom_contenttype" style="width:200px;" size="5">
            <?php
	            $custom_contenttype = (isset($custom_contenttype) ? $custom_contenttype : "text/css,text/html,text/javascript,text/plain,text/xml");
            	$ct = explode(",",$custom_contenttype);
            	for($i=0;$i<count($ct);$i++) {
            		echo "<option value=\"".$ct[$i]."\">".$ct[$i]."</option>";
            	}
            ?>
            </select>
            <input name="custom_contenttype" type="hidden" value="<?php echo $custom_contenttype; ?>" />
            </td><td valign="top">&nbsp;<input name="removecontenttype" type="button" value="<?php echo $_lang["remove"]; ?>" onclick='removeContentType()' /></td></tr></table>
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["custom_contenttype_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
           </tr> 
<tr>
	<td nowrap class="warning" valign="top"><b><?php echo $_lang["docid_incrmnt_method_title"] ?></b></td>
	<td>
	<input onchange="documentDirty=true;" type="radio" name="docid_incrmnt_method" value="0" 
			<?php echo ($docid_incrmnt_method=='0' || !isset($auto_menuindex)) ? 'checked="checked"' : "" ; ?> /> 
			<?php echo $_lang["docid_incrmnt_method_0"]?><br /> 
			
	<input onchange="documentDirty=true;" type="radio" name="docid_incrmnt_method" value="1" 
			<?php echo ($docid_incrmnt_method=='1') ? 'checked="checked"' : "" ; ?> /> 
			<?php echo $_lang["docid_incrmnt_method_1"]?><br /> 
        <input onchange="documentDirty=true;" type="radio" name="docid_incrmnt_method" value="2" 
			<?php echo ($docid_incrmnt_method=='2') ? 'checked="checked"' : "" ; ?> /> 
			<?php echo $_lang["docid_incrmnt_method_2"]?><br /> 
	</td>
</tr>
<tr> 
            <td colspan="2"><div class='split'></div></td> 
          </tr>     
           
           <tr>
    <td nowrap class="warning"><b><?php echo $_lang["cache_type_title"] ?></b></td>
  <td>
      
  <input onchange="documentDirty=true;" type="radio" name="cache_type" value="1" 
      <?php echo ($cache_type=='1') ? 'checked="checked"' : "" ; ?> /> 
      <?php echo $_lang["cache_type_1"]?><br /> 
      
  <input onchange="documentDirty=true;" type="radio" name="cache_type" value="2" 
      <?php echo ($cache_type=='2') ? 'checked="checked"' : "" ; ?> /> 
      <?php echo $_lang["cache_type_2"]?><br /> 
  </td>
</tr>

          <tr>
            <td colspan="2"><div class='split'></div></td>
            </tr>
            <tr>
              <td nowrap class="warning"><b><?php echo $_lang["serveroffset_title"] ?></b></td>
              <td> <select name="server_offset_time" size="1" class="inputBox">
                  <?php
  			for($i=-24; $i<25; $i++) {
  				$seconds = $i*60*60;
  				$selectedtext = $seconds==$server_offset_time ? "selected='selected'" : "" ;
  			?>
                  <option value="<?php echo $seconds; ?>" <?php echo $selectedtext; ?>><?php echo $i; ?></option>
                  <?php
  			}
  			?>
                </select> </td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php printf($_lang["serveroffset_message"], strftime('%H:%M:%S', time()), strftime('%H:%M:%S', time()+$server_offset_time)); ?></td>
            </tr>
            <tr>
              <td colspan="2"><div class='split'>&nbsp;</div></td>
            </tr>
            <tr>
              <td nowrap class="warning"><b><?php echo $_lang["server_protocol_title"] ?></b></td>
              <td> <input onchange="documentDirty=true;" type="radio" name="server_protocol" value="http" <?php echo ($server_protocol=='http' || !isset($server_protocol))? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["server_protocol_http"]?><br />
                <input onchange="documentDirty=true;" type="radio" name="server_protocol" value="https" <?php echo $server_protocol=='https' ? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["server_protocol_https"]?> </td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["server_protocol_message"] ?></td>
            </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
            <tr>
              <td nowrap class="warning"><b><?php echo $_lang["validate_referer_title"] ?></b></td>
              <td><input onchange="documentDirty=true;" type="radio" name="validate_referer" value="1" <?php echo ($validate_referer=='1' || !isset($validate_referer)) ? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["yes"]?><br />
                <input onchange="documentDirty=true;" type="radio" name="validate_referer" value="0" <?php echo $validate_referer=='0' ? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["no"]?> </td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["validate_referer_message"] ?></td>
            </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
			<tr>
              <td nowrap class="warning"><?php echo $_lang["rss_url_news_title"] ?></td>
              <td ><input onchange="documentDirty=true;" type='text' maxlength='350' style="width: 350px;" name="rss_url_news" value="<?php echo isset($rss_url_news) ? $rss_url_news : $_lang["rss_url_news_default"] ; ?>" /></td>
            </tr>
			<tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["rss_url_news_message"] ?></td>
            </tr>
			<tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
			<tr>
              <td nowrap class="warning"><?php echo $_lang["rss_url_security_title"] ?></td>
              <td ><input onchange="documentDirty=true;" type='text' maxlength='350' style="width: 350px;" name="rss_url_security" value="<?php echo isset($rss_url_security) ? $rss_url_security : $_lang["rss_url_security_default"] ; ?>" /></td>
            </tr>
			<tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["rss_url_security_message"] ?></td>
            </tr>
			<tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
		  <tr class='row1'>
            <td colspan="2">
		        <?php
					// invoke OnSiteSettingsRender event
					$evtOut = $modx->invokeEvent("OnSiteSettingsRender");
					if(is_array($evtOut)) echo implode("",$evtOut);
		        ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Friendly URL settings  -->
      <div class="tab-page" id="tabPage3">
        <h2 class="tab"><?php echo $_lang["settings_furls"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage3" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["friendlyurls_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="friendly_urls" value="1" <?php echo $friendly_urls=='1' ? 'checked="checked"' : "" ; ?> onclick='showHide(/furlRow/, 1);' />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="friendly_urls" value="0" <?php echo ($friendly_urls=='0' || !isset($friendly_urls)) ? 'checked="checked"' : "" ; ?> onclick='showHide(/furlRow/, 0);' />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["friendlyurls_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='furlRow1' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["friendlyurlsprefix_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' style="width: 200px;" name="friendly_url_prefix" value="<?php echo isset($friendly_url_prefix) ? $friendly_url_prefix : "p" ; ?>" /></td>
          </tr>
          <tr id='furlRow2' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["friendlyurlsprefix_message"] ?></td>
          </tr>
          <tr id='furlRow3' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='furlRow4' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["friendlyurlsuffix_title"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' style="width: 200px;" name="friendly_url_suffix" value="<?php echo isset($friendly_url_suffix) ? $friendly_url_suffix : ".html" ; ?>" /></td>
          </tr>
          <tr id='furlRow5' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["friendlyurlsuffix_message"] ?></td>
          </tr>
          <tr id='furlRow6' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
<?php if(!isset($make_folders)) $make_folders = '0';?>
<tr id="furlRow51" class="furlRow row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
  <th><?php echo $_lang['make_folders_title'] ?></th>
  <td>
    <?php echo wrap_label($_lang["yes"],form_radio('make_folders','1', $make_folders=='1'));?><br />
    <?php echo wrap_label($_lang["no"],form_radio('make_folders','0', $make_folders=='0'));?><br />
</td>
  </tr>
  <tr id='furlRow56' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["make_folders_message"] ?></td>
</tr> 
<tr id='furlRow52' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
<td colspan="2"><div class='split'></div></td>
</tr>
      	
  <?php if(!isset($seostrict)) $seostrict = '0';?>
  <tr id="furlRow53" class="furlRow row1" style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
    <th><?php echo $_lang['seostrict_title'] ?></th>
    <td>
      <?php echo wrap_label($_lang["yes"],form_radio('seostrict','1', $seostrict=='1'));?><br />
      <?php echo wrap_label($_lang["no"],form_radio('seostrict','0', $seostrict=='0'));?><br />
     </td> 
  </tr>
  <tr id='furlRow54' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["seostrict_message"] ?></td>
          </tr> 
  <tr id='furlRow55' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
  <td colspan="2"><div class='split'></div></td>
  </tr>


          <tr id='furlRow7' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["friendly_alias_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="friendly_alias_urls" value="1" <?php echo $friendly_alias_urls=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="friendly_alias_urls" value="0" <?php echo ($friendly_alias_urls=='0' || !isset($friendly_alias_urls)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='furlRow8' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["friendly_alias_message"] ?></td>
          </tr>
          <tr id='furlRow9' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='furlRow10' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["use_alias_path_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="use_alias_path" value="1" <?php echo $use_alias_path=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="use_alias_path" value="0" <?php echo ($use_alias_path=='0' || !isset($use_alias_path)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='furlRow11' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["use_alias_path_message"] ?></td>
          </tr>
          <tr id='furlRow12' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='furlRow16' class='row2' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["duplicate_alias_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="allow_duplicate_alias" value="1" <?php echo $allow_duplicate_alias=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="allow_duplicate_alias" value="0" <?php echo ($allow_duplicate_alias=='0' || !isset($allow_duplicate_alias)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='furlRow17' class='row2' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["duplicate_alias_message"] ?></td>
          </tr>
          <tr id='furlRow18' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='furlRow13' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["automatic_alias_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="automatic_alias" value="1" <?php echo $automatic_alias=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="automatic_alias" value="0" <?php echo ($automatic_alias=='0' || !isset($automatic_alias)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='furlRow14' class='row1' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["automatic_alias_message"] ?></td>
          </tr>
          <tr id='furlRow15' style="display: <?php echo $friendly_urls==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
		  <tr class='row1'>
            <td colspan="2">
		        <?php
					// invoke OnFriendlyURLSettingsRender event
					$evtOut = $modx->invokeEvent("OnFriendlyURLSettingsRender");
					if(is_array($evtOut)) echo implode("",$evtOut);
		        ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- User settings -->
      <div class="tab-page" id="tabPage4">
        <h2 class="tab"><?php echo $_lang["settings_users"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage4" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
        <?php
          if(!isset($check_files_onlogin))
            $check_files_onlogin="index.php\n.htaccess\nmanager/index.php\nmanager/includes/config.inc.php";
        ?>
        <tr>
            <th><?php echo $_lang["check_files_onlogin_title"] ?></th>
            <td>
              <textarea name="check_files_onlogin"><?php echo $check_files_onlogin;?></textarea><br />
                
        </td>
        </tr>
        <tr  class='row1' >
            <td width="200">&nbsp;</td>
            <td class='comment'>  <?php echo $_lang["check_files_onlogin_message"] ?></td>
      </tr> 
        <tr>
          <td colspan="2"><div class='split'></div></td>
        </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["udperms_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="use_udperms" value="1" <?php echo $use_udperms=='1' ? 'checked="checked"' : "" ; ?> onclick='showHide(/udPerms/, 1);' />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="use_udperms" value="0" <?php echo ($use_udperms=='0' || !isset($use_udperms)) ? 'checked="checked"' : "" ; ?> onclick='showHide(/udPerms/, 0);' />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["udperms_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='udPermsRow1' class='row1' style="display: <?php echo $use_udperms==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["udperms_allowroot_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="udperms_allowroot" value="1" <?php echo $udperms_allowroot=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="udperms_allowroot" value="0" <?php echo ($udperms_allowroot=='0' || !isset($udperms_allowroot)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='udPermsRow2' class='row1' style="display: <?php echo $use_udperms==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["udperms_allowroot_message"] ?></td>
          </tr>
          <tr id='udPermsRow3' style="display: <?php echo $use_udperms==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["failed_login_title"] ?></b></td>
            <td><input type="text" name="failed_login_attempts" style="width:50px" value="<?php echo isset($failed_login_attempts) ? $failed_login_attempts : "3" ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["failed_login_message"] ?></td>
          </tr>
           <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["blocked_minutes_title"] ?></b></td>
            <td><input type="text" name="blocked_minutes" style="width:100px" value="<?php echo isset($blocked_minutes) ? $blocked_minutes : "60" ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["blocked_minutes_message"] ?></td>
          </tr>
           <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
		<tr>
		<?php if(!isset($error_reporting)) $error_reporting = '1'; ?>
		<th><?php echo $_lang['a17_error_reporting_title']; ?></th>
		<td>
			<?php echo wrap_label($_lang['a17_error_reporting_opt0'], form_radio('error_reporting','0' , $error_reporting==='0'));?><br />
			<?php echo wrap_label($_lang['a17_error_reporting_opt1'], form_radio('error_reporting','1' , $error_reporting==='1'));?><br />
			<?php echo wrap_label($_lang['a17_error_reporting_opt2'], form_radio('error_reporting','2' , $error_reporting==='2'));?><br />
			<?php echo wrap_label($_lang['a17_error_reporting_opt99'],form_radio('error_reporting','99', $error_reporting==='99'));?><br />
   </td>
    </tr>
    <tr  class='row1' >
            <td width="200">&nbsp;</td>
            <td class='comment'> <?php echo $_lang['a17_error_reporting_msg'];?></td>
    </tr>
<tr><td colspan="2"><div class='split'></div></td></tr>
<?php if(!isset($send_errormail)) $send_errormail='3';?>
<tr>
<th><?php echo $_lang['mutate_settings.dynamic.php6']; ?></th>
<td>
	<?php echo wrap_label($_lang['mutate_settings.dynamic.php7'],form_radio('send_errormail','0', ($send_errormail=='0' || !isset($send_errormail))));?><br />
	<?php echo wrap_label('error',form_radio('send_errormail','3', $send_errormail=='3'));?><br />
	<?php echo wrap_label('error + warning',form_radio('send_errormail','2', $send_errormail=='2'));?><br />
	<?php echo wrap_label('error + warning + information',form_radio('send_errormail','1', $send_errormail=='1'));?><br />
<?php echo parsePlaceholder($_lang['mutate_settings.dynamic.php8'],array('emailsender'=>$modx->config['emailsender']));?></td>
</tr>
    
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
			<tr>
			<th><?php echo $_lang["pwd_hash_algo_title"] ?></th>
			<td>
			<?php
				$phm['sel']['BLOWFISH_Y'] = $pwd_hash_algo=='BLOWFISH_Y' ?  1 : 0;
				$phm['sel']['BLOWFISH_A'] = $pwd_hash_algo=='BLOWFISH_A' ?  1 : 0;
				$phm['sel']['SHA512']     = $pwd_hash_algo=='SHA512' ?  1 : 0;
				$phm['sel']['SHA256']     = $pwd_hash_algo=='SHA256' ?  1 : 0;
				$phm['sel']['MD5']        = $pwd_hash_algo=='MD5' ?  1 : 0;
				$phm['sel']['UNCRYPT']    = $pwd_hash_algo=='UNCRYPT' ?  1 : 0;
				if(!isset($pwd_hash_algo) || empty($pwd_hash_algo)) $phm['sel']['UNCRYPT'] = 1;
				$phm['e']['BLOWFISH_Y'] = $modx->manager->checkHashAlgorithm('BLOWFISH_Y') ? 0:1;
				$phm['e']['BLOWFISH_A'] = $modx->manager->checkHashAlgorithm('BLOWFISH_A') ? 0:1;
				$phm['e']['SHA512']     = $modx->manager->checkHashAlgorithm('SHA512') ? 0:1;
				$phm['e']['SHA256']     = $modx->manager->checkHashAlgorithm('SHA256') ? 0:1;
				$phm['e']['MD5']        = $modx->manager->checkHashAlgorithm('MD5') ? 0:1;
				$phm['e']['UNCRYPT']    = $modx->manager->checkHashAlgorithm('UNCRYPT') ? 0:1;
			?>
				<?php echo wrap_label('CRYPT_BLOWFISH_Y (salt &amp; stretch)',form_radio('pwd_hash_algo','BLOWFISH_Y',$phm['sel']['BLOWFISH_Y'], '', $phm['e']['BLOWFISH_Y']));?><br />
				<?php echo wrap_label('CRYPT_BLOWFISH_A (salt &amp; stretch)',form_radio('pwd_hash_algo','BLOWFISH_A',$phm['sel']['BLOWFISH_A'], '', $phm['e']['BLOWFISH_A']));?><br />
				<?php echo wrap_label('CRYPT_SHA512 (salt &amp; stretch)'    ,form_radio('pwd_hash_algo','SHA512'    ,$phm['sel']['SHA512']    , '', $phm['e']['SHA512']));?><br />
				<?php echo wrap_label('CRYPT_SHA256 (salt &amp; stretch)'    ,form_radio('pwd_hash_algo','SHA256'    ,$phm['sel']['SHA256']    , '', $phm['e']['SHA256']));?><br />
				<?php echo wrap_label('CRYPT_MD5 (salt &amp; stretch)'       ,form_radio('pwd_hash_algo','MD5'       ,$phm['sel']['MD5']       , '', $phm['e']['MD5']));?><br />
				<?php echo wrap_label('UNCRYPT(32 chars salt + SHA-1 hash)'   ,form_radio('pwd_hash_algo','UNCRYPT'   ,$phm['sel']['UNCRYPT']   , '', $phm['e']['UNCRYPT']));?><br />
       
			</td>
			</tr>
      <tr  class='row1' >
            <td width="200">&nbsp;</td>
            <td class='comment'>  <?php echo $_lang["pwd_hash_algo_message"]?></td>
      </tr> 
           <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
		<tr>
			<th><?php echo $_lang["enable_bindings_title"] ?></th>
			<td>
				<?php echo wrap_label($_lang["yes"],form_radio('enable_bindings','1',$enable_bindings=='1' || !isset($enable_bindings)));?><br />
				<?php echo wrap_label($_lang["no"], form_radio('enable_bindings','0',$enable_bindings=='0'));?><br />
       
		</td>
		</tr>
          <tr  class='row1' >
            <td width="200">&nbsp;</td>
            <td class='comment'>  <?php echo $_lang["enable_bindings_message"] ?></td>
      </tr>
           <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <?php
              // Check for GD before allowing captcha to be enabled
              $gdAvailable = extension_loaded('gd');
          ?>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["captcha_title"] ?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="use_captcha" value="1" <?php echo ($use_captcha=='1' && $gdAvailable) ? 'checked="checked"' : "" ; echo (!$gdAvailable)? ' readonly="readonly"' : ''; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="use_captcha" value="0" <?php echo ($use_captcha=='0' || !isset($use_captcha) || !$gdAvailable) ? 'checked="checked"' : "" ; echo (!$gdAvailable)? ' readonly="readonly"' : '';?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["captcha_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["captcha_words_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_captcha_words" id="reload_captcha_words_select" onchange="confirmLangChange(this, 'captcha_words_default', 'captcha_words_input');">
<?php echo get_lang_options('captcha_words_default');?>
              </select>
            </td>
            <td><input type="text" id="captcha_words_input" name="captcha_words" style="width:250px" value="<?php echo isset($captcha_words) ? $captcha_words : $_lang["captcha_words_default"] ; ?>" />
                <input type="hidden" name="captcha_words_default" id="captcha_words_default_hidden" value="<?php echo addslashes($_lang["captcha_words_default"]);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["captcha_words_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["emailsender_title"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="emailsender" value="<?php echo isset($emailsender) ? $emailsender : "you@example.com" ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["emailsender_message"] ?></td>
          </tr>

          <!--for smtp-->

          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["email_method_title"] ?></b></td>
            <td>
                <?php echo wrap_label($_lang["email_method_mail"],form_radio('email_method','mail', ($email_method=='mail' || !isset($email_method)) ));?><br />
                <?php echo wrap_label($_lang["email_method_smtp"],form_radio('email_method','smtp', ($email_method=='smtp') ));?><br />
            </td>
          </tr>
          <tr>
            <td colspan="2"><div class="split"></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["smtp_auth_title"] ?></b></td>
            <td>
                <?php echo wrap_label($_lang["yes"],form_radio('smtp_auth','1', ($smtp_auth=='1' || !isset($smtp_auth)) ));?><br />
                <?php echo wrap_label($_lang["no"],form_radio('smtp_auth','0', ($smtp_auth=='0') ));?><br />
            </td>
          </tr>
          <tr>
            <td colspan="2"><div class="split"></div></td>
          </tr>

          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["smtp_host_title"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="smtp_host" value="<?php echo isset($smtp_host) ? $smtp_host : "smtp.example.com" ; ?>" /></td>
          </tr>
          <tr>
            <td colspan="2"><div class="split"></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["smtp_port_title"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="smtp_port" value="<?php echo isset($smtp_port) ? $smtp_port : "25" ; ?>" /></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["smtp_username_title"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="smtp_username" value="<?php echo isset($smtp_username) ? $smtp_username : $emailsender ; ?>" /></td>
          </tr>
           <tr>
            <td colspan="2"><div class="split"></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["smtp_password_title"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type="password" maxlength="255" style="width: 250px;" name="smtppw" value="********************" autocomplete="off" /></td>
          </tr>
           <tr>
            <td colspan="2"><div class="split"></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["emailsubject_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_emailsubject" id="reload_emailsubject_select" onchange="confirmLangChange(this, 'emailsubject_default', 'emailsubject_field');">
<?php echo get_lang_options('emailsubject_default');?>
              </select>
            </td>
            <td ><input id="emailsubject_field" name="emailsubject" onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" value="<?php echo isset($emailsubject) ? $emailsubject : $_lang["emailsubject_default"] ; ?>" />
                <input type="hidden" name="emailsubject_default" id="emailsubject_default_hidden" value="<?php echo addslashes($_lang['emailsubject_default']);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["emailsubject_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["signupemail_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_signupemail_message" id="reload_signupemail_message_select" onchange="confirmLangChange(this, 'system_email_signup', 'signupemail_message_textarea');">
<?php echo get_lang_options('system_email_signup');?>
              </select>
            </td>
            <td> <textarea id="signupemail_message_textarea" name="signupemail_message" style="width:100%; height: 120px;"><?php echo isset($signupemail_message) ? $signupemail_message : $_lang["system_email_signup"] ?></textarea>
                <input type="hidden" name="system_email_signup_default" id="system_email_signup_hidden" value="<?php echo addslashes($_lang['system_email_signup']);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["signupemail_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["websignupemail_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_websignupemail_message" id="reload_websignupemail_message_select" onchange="confirmLangChange(this, 'system_email_websignup', 'websignupemail_message_textarea');">
<?php echo get_lang_options('system_email_websignup');?>
              </select>
            </td>
            <td> <textarea id="websignupemail_message_textarea" name="websignupemail_message" style="width:100%; height: 120px;"><?php echo isset($websignupemail_message) ? $websignupemail_message : $_lang["system_email_websignup"] ?></textarea>
                <input type="hidden" name="system_email_websignup_default" id="system_email_websignup_hidden" value="<?php echo addslashes($_lang['system_email_websignup']);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["websignupemail_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning" valign="top"><b><?php echo $_lang["webpwdreminder_title"] ?></b>
              <br />
              <p><?php echo $_lang["update_settings_from_language"]; ?></p>
              <select name="reload_system_email_webreminder_message" id="reload_system_email_webreminder_select" onchange="confirmLangChange(this, 'system_email_webreminder', 'system_email_webreminder_textarea');">
<?php echo get_lang_options('system_email_webreminder');?>
              </select>
            </td>
            <td> <textarea id="system_email_webreminder_textarea" name="webpwdreminder_message" style="width:100%; height: 120px;"><?php echo isset($webpwdreminder_message) ? $webpwdreminder_message : $_lang["system_email_webreminder"]; ?></textarea>
                <input type="hidden" name="system_email_webreminder_default" id="system_email_webreminder_hidden" value="<?php echo addslashes($_lang['system_email_webreminder']);?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["webpwdreminder_message"] ?></td>
          </tr>
		  <tr class='row1'>
            <td colspan="2">
		        <?php
					// invoke OnUserSettingsRender event
					$evtOut = $modx->invokeEvent("OnUserSettingsRender");
					if(is_array($evtOut)) echo implode("",$evtOut);
		        ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Interface & editor settings -->
      <div class="tab-page" id="tabPage5">
        <h2 class="tab"><?php echo $_lang["settings_ui"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage5" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
            <tr>
              <td nowrap class="warning"><b><?php echo $_lang["manager_theme"]?></b></td>
              <td> <select name="manager_theme" size="1" class="inputBox" onchange="documentDirty=true;document.forms['settings'].theme_refresher.value = Date.parse(new Date())">
               <?php
      			$dir = dir("media/style/");
      			while ($file = $dir->read()) {
      				if($file!="." && $file!=".." && is_dir("media/style/$file") && substr($file,0,1) != '.') {
      					if($file==='common') continue;
      					$themename = $file;
      					$selectedtext = $themename==$manager_theme ? "selected='selected'" : "" ;
      	            	echo "<option value='$themename' $selectedtext>".ucwords(str_replace("_", " ", $themename))."</option>";
      				}
      			}
      			$dir->close();
      		 ?>
               </select><input type="hidden" name="theme_refresher" value="" /></td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["manager_theme_message"]?></td>
            </tr>
            
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
             <tr>
               <td nowrap class="warning"><b><?php echo $_lang["warning_visibility"] ?></b></td>
               <td> <input onchange="documentDirty=true;" type="radio" name="warning_visibility" value="0" <?php echo $warning_visibility=='0' ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["administrators"]?><br />
                 <input onchange="documentDirty=true;" type="radio" name="warning_visibility" value="1" <?php echo (!isset($warning_visibility) || $warning_visibility=='1') ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["everybody"]?></td>
             </tr>
             <tr>
               <td width="200">&nbsp;</td>
               <td class='comment'><?php echo $_lang["warning_visibility_message"]?></td>
             </tr>
            
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
             <tr>
      		   <td nowrap class="warning"><b><?php echo $_lang["tree_page_click"] ?></b></td>
      		   <td> <input onchange="documentDirty=true;" type="radio" name="tree_page_click" value="27" <?php echo $tree_page_click=='27' ? 'checked="checked"' : ""; ?> />
      			 <?php echo $_lang["edit_resource"]?><br />
      			 <input onchange="documentDirty=true;" type="radio" name="tree_page_click" value="3" <?php echo ($tree_page_click=='3' || !isset($tree_page_click)) ? 'checked="checked"' : ""; ?> />
      			 <?php echo $_lang["doc_data_title"]?></td>
      		 </tr>
             <tr>
               <td width="200">&nbsp;</td>
               <td class='comment'><?php echo $_lang["tree_page_click_message"]?></td>
             </tr>
             <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
             <tr>
               <td nowrap class="warning"><b><?php echo $_lang["remember_last_tab"] ?></b></td>
               <td> <input onchange="documentDirty=true;" type="radio" name="remember_last_tab" value="1" <?php echo $remember_last_tab=='1' ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["yes"]?><br />
                 <input onchange="documentDirty=true;" type="radio" name="remember_last_tab" value="0" <?php echo (!isset($remember_last_tab) || $remember_last_tab=='0') ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["no"]?></td>
             </tr>
             <tr>
               <td width="200">&nbsp;</td>
               <td class='comment'><?php echo $_lang["remember_last_tab_message"]?></td>
             </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
<?php
    if(!isset($resource_tree_node_name)) $resource_tree_node_name = 'pagetitle';
?>
<tr>
<th><?php echo $_lang["setting_resource_tree_node_name"] ?></th>
<td>
	<select name="resource_tree_node_name" size="1" class="inputBox">
<?php
	$tpl = '<option value="[+value+]" [+selected+]>[*[+value+]*]</option>' . "\n";
	$option = array('pagetitle','menutitle','alias','createdon','editedon','publishedon');
	$output = array();
	foreach($option as $v)
	{
		$selected = ($v==$resource_tree_node_name) ? 'selected' : '';
		$s = array('[+value+]','[+selected+]');
		$r = array($v,$selected);
		$output[] = str_replace($s,$r,$tpl);
	}
	echo join("\n",$output)
?>
	</select><br />
	
</td>
</tr>
        <tr  class='row1' >
            <td width="200">&nbsp;</td>
            <td class='comment'>  <?php echo $_lang["setting_resource_tree_node_name_desc"]?></td>
      </tr> 
<tr>
  <td colspan="2"><div class='split'></div></td>
</tr>
            	<tr>
      		   <td nowrap class="warning"><b><?php echo $_lang["tree_show_protected"] ?></b></td>
      		   <td> <input onchange="documentDirty=true;" type="radio" name="tree_show_protected" value="1" <?php echo (!isset($tree_show_protected) || $tree_show_protected=='0') ? '' : 'checked="checked" '; ?>/>
      			 <?php echo $_lang["yes"]?><br />
      			 <input onchange="documentDirty=true;" type="radio" name="tree_show_protected" value="0" <?php echo (!isset($tree_show_protected) || $tree_show_protected=='0') ? 'checked="checked" ' : ''; ?>/>
      			 <?php echo $_lang["no"]?></td>
      		 </tr>
                 <tr>
                   <td width="200">&nbsp;</td>
                   <td class='comment'><?php echo $_lang["tree_show_protected_message"]?></td>
                 </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
             <tr>
      		   <td nowrap class="warning"><b><?php echo $_lang["show_meta"] ?></b></td>
      		   <td> <input onchange="documentDirty=true;" type="radio" name="show_meta" value="1" <?php echo $show_meta=='1' ? 'checked="checked"' : ""; ?> />
      			 <?php echo $_lang["yes"]?><br />
      			 <input onchange="documentDirty=true;" type="radio" name="show_meta" value="0" <?php echo ($show_meta=='0' || !isset($show_meta)) ? 'checked="checked"' : ""; ?> />
      			 <?php echo $_lang["no"]?></td>
      		 </tr>
             <tr>
               <td width="200">&nbsp;</td>
               <td class='comment'><?php echo $_lang["show_meta_message"]?></td>
             </tr>
<tr>
  <td colspan="2"><div class='split'></div></td>
</tr>

             <tr>
      		   <td nowrap class="warning"><b><?php echo $_lang["datepicker_offset"] ?></b></td>
      		   <td><input onchange="documentDirty=true;" type='text' maxlength='50' size="5" name="datepicker_offset" value="<?php echo isset($datepicker_offset) ? $datepicker_offset : '-10' ; ?>" /></td>
      		 </tr>
      		 <tr>
                   <td width="200">&nbsp;</td>
                   <td class='comment'><?php echo $_lang["datepicker_offset_message"]?></td>
             </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
            <tr>
              <td nowrap class="warning"><b><?php echo $_lang["datetime_format"]?></b></td>
              <td> <select name="datetime_format" size="1" class="inputBox">
              <?php
                  $datetime_format_list = array('dd-mm-YYYY', 'mm/dd/YYYY', 'YYYY/mm/dd');
                  $str = '';
                  foreach($datetime_format_list as $value)
                  {
                      $selectedtext = ($datetime_format == $value) ? ' selected' : '';
                      $str .= '<option value="' . $value . '"' . $selectedtext . '>';
                      $str .= $value . '</option>' . PHP_EOL;
                  }
                  echo $str;
              ?>
               </select></td>
            </tr>
            <tr>
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["datetime_format_message"]?></td>
            </tr>
            <tr>
              <td colspan="2"><div class='split'></div></td>
            </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["nologentries_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' size="5" name="number_of_logs" value="<?php echo isset($number_of_logs) ? $number_of_logs : 100 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["nologentries_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["mail_check_timeperiod_title"] ?></b></td>
            <td><input type="text" name="mail_check_timeperiod" onchange="documentDirty=true;" size="5" value="<?php echo isset($mail_check_timeperiod) ? $mail_check_timeperiod : "60" ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["mail_check_timeperiod_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["nomessages_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' size="5" name="number_of_messages" value="<?php echo isset($number_of_messages) ? $number_of_messages : 30 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["nomessages_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["noresults_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='50' size="5" name="number_of_results" value="<?php echo isset($number_of_results) ? $number_of_results : 30 ; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["noresults_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["settings_strip_image_paths_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type="radio" name="strip_image_paths" value="1" <?php echo $strip_image_paths=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="strip_image_paths" value="0" <?php echo ($strip_image_paths=='0' || !isset($strip_image_paths)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["settings_strip_image_paths_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["rb_title"]?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="use_browser" value="1" <?php echo ($use_browser=='1' || !isset($use_browser)) ? 'checked="checked"' : "" ; ?> onclick="showHide(/rbRow/, 1);" />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="use_browser" value="0" <?php echo $use_browser=='0' ? 'checked="checked"' : "" ; ?> onclick="showHide(/rbRow/, 0);" />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["rb_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          
          <?php if(!isset($use_browser)) $use_browser=1; ?>
          
          <tr id='rbRow1' class="" style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["rb_webuser_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type="radio" name="rb_webuser" value="1" <?php echo $rb_webuser=='1' ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="rb_webuser" value="0" <?php echo ($rb_webuser=='0' || !isset($rb_webuser)) ? 'checked="checked"' : "" ; ?> />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr id='rbRow2' class="" style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class="comment"><?php echo $_lang["rb_webuser_message"]?></td>
          </tr>
          <tr id='rbRow3' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='rbRow4' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["rb_base_dir_title"]?></b></td>
            <td>
                <?php echo $_lang['default']; ?> <span id="default_rb_base_dir">[(base_path)]assets/</span><br />
                <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="rb_base_dir" id="rb_base_dir" value="<?php echo (isset($rb_base_dir)) ? $rb_base_dir : '[(base_path)]assets/' ; ?>" /> <input type="button" onclick="reset_path('rb_base_dir');" value="<?php echo $_lang["reset"]; ?>" name="reset_rb_base_dir">
            </td>
          </tr>
          <tr id='rbRow5' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["rb_base_dir_message"]?></td>
          </tr>
          <tr id='rbRow6' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='rbRow7' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["rb_base_url_title"]?></b></td>
            <td> <?php
				function getResourceBaseUrl() {
					global $site_url;
					return $site_url . "assets/";
				}
				?>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="rb_base_url" value="<?php echo isset($rb_base_url) ? $rb_base_url : getResourceBaseUrl() ; ?>" />
              </td>
          </tr>
          <tr id='rbRow8' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["rb_base_url_message"]?></td>
          </tr>
          <tr id='rbRow9' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='rbRow10' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["uploadable_images_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="upload_images" value="<?php echo isset($upload_images) ? $upload_images : "jpg,gif,png,ico,bmp,psd" ; ?>" />
            </td>
          </tr>
          <tr id='rbRow11' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["uploadable_images_message"]?></td>
          </tr>
          <tr id='rbRow12' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
          <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='rbRow13' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["uploadable_media_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="upload_media" value="<?php echo isset($upload_media) ? $upload_media : "mp3,wav,au,wmv,avi,mpg,mpeg" ; ?>" />
            </td>
          </tr>
          <tr id='rbRow14' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["uploadable_media_message"]?></td>
          </tr>
          <tr id='rbRow15' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
          <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='rbRow16' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["uploadable_flash_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="upload_flash" value="<?php echo isset($upload_flash) ? $upload_flash : "swf,fla" ; ?>" />
            </td>
          </tr>
            <tr id='rbRow17' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["uploadable_flash_message"]?></td>
            </tr>
            <tr id='rbRow171' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
              <td colspan="2"><div class='split'></div></td>
            </tr>
          <tr id='rbRow172' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
              <td nowrap class="warning"><b><?php echo $_lang["clean_uploaded_filename"]?></b></td>
              <td> <input onchange="documentDirty=true;" type="radio" name="clean_uploaded_filename" value="1" <?php echo $clean_uploaded_filename=='1' ? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["yes"]?><br />
                <input onchange="documentDirty=true;" type="radio" name="clean_uploaded_filename" value="0" <?php echo ($clean_uploaded_filename=='0' || !isset($clean_uploaded_filename)) ? 'checked="checked"' : "" ; ?> />
                <?php echo $_lang["no"]?> </td>
          </tr>
            <tr id='rbRow17' class='' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
              <td width="200">&nbsp;</td>
              <td class='comment'><?php echo $_lang["clean_uploaded_filename_message"];?></td>
            </tr>
          <tr id='rbRow18' style="display: <?php echo $use_browser==1 ? $displayStyle : 'none' ; ?>">
          <td colspan="2"><div class='split'></div></td>
          </tr>

          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["use_editor_title"]?></b></td>
            <td> <input onchange="documentDirty=true;" type="radio" name="use_editor" value="1" <?php echo ($use_editor=='1' || !isset($use_editor)) ? 'checked="checked"' : "" ; ?> onclick="showHide(/editorRow/, 1); checkCustomIcons();" />
              <?php echo $_lang["yes"]?><br />
              <input onchange="documentDirty=true;" type="radio" name="use_editor" value="0" <?php echo $use_editor=='0' ? 'checked="checked"' : "" ; ?> onclick="showHide(/editorRow/, 0);" />
              <?php echo $_lang["no"]?> </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["use_editor_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          
          <?php if(!isset($use_editor)) $use_editor=1; ?>
          
          <tr id='editorRow0' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["which_editor_title"]?></b></td>
            <td>
				<select name="which_editor" onchange="documentDirty=true;">
					<?php
						// invoke OnRichTextEditorRegister event
						$evtOut = $modx->invokeEvent("OnRichTextEditorRegister");
						echo "<option value='none'".($which_editor=='none' ? " selected='selected'" : "").">".$_lang["none"]."</option>\n";
						if(is_array($evtOut)) for($i=0;$i<count($evtOut);$i++) {
							$editor = $evtOut[$i];
							echo "<option value='$editor'".($which_editor==$editor ? " selected='selected'" : "").">$editor</option>\n";
						}
					?>
				</select>
			</td>
          </tr>
          <tr id='editorRow1' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["which_editor_message"]?></td>
          </tr>
          <tr id='editorRow3' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='editorRow4' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["fe_editor_lang_title"]?></b></td>
            <td> <select name="fe_editor_lang" size="1" class="inputBox" onchange="documentDirty=true;">
<?php echo get_lang_options(null, $fe_editor_lang);?>
              </select> </td>
          </tr>
          <tr id='editorRow5' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["fe_editor_lang_message"]?></td>
          </tr>
          <tr id='editorRow2' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr id='editorRow14' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td nowrap class="warning"><b><?php echo $_lang["editor_css_path_title"]?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="editor_css_path" value="<?php echo isset($editor_css_path) ? $editor_css_path : "" ; ?>" />
			</td>
          </tr>
          <tr id='editorRow15' class='' style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["editor_css_path_message"]?></td>
          </tr>
		  <tr id='editorRow16' class="" style="display: <?php echo $use_editor==1 ? $displayStyle : 'none' ; ?>">
            <td colspan="2"><div class='split'></div></td>
          </tr>
		  <tr class='row1'>
            <td colspan="2">
		        <?php
					// invoke OnInterfaceSettingsRender event
					$evtOut = $modx->invokeEvent("OnInterfaceSettingsRender");
					if(is_array($evtOut)) echo implode("",$evtOut);
		        ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Miscellaneous settings -->
      <div class="tab-page" id="tabPage7">
        <h2 class="tab"><?php echo $_lang["settings_misc"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage7" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["filemanager_path_title"]?></b></td>
            <td>
              <?php echo $_lang['default']; ?> <span id="default_filemanager_path">[(base_path)]</span><br />
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="filemanager_path" id="filemanager_path" value="<?php echo isset($filemanager_path) ? $filemanager_path : '[(base_path)]'; ?>" /> <input type="button" onclick="reset_path('filemanager_path');" value="<?php echo $_lang["reset"]; ?>" name="reset_filemanager_path">
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["filemanager_path_message"]?></td>
          </tr>
          <tr>
          <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["uploadable_files_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="upload_files" value="<?php echo isset($upload_files) ? $upload_files : "txt,php,html,htm,xml,js,css,cache,zip,gz,rar,z,tgz,tar,htaccess,pdf" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["uploadable_files_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["upload_maxsize_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="upload_maxsize" value="<?php echo isset($upload_maxsize) ? $upload_maxsize : "1048576" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["upload_maxsize_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["new_file_permissions_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="new_file_permissions" value="<?php echo isset($new_file_permissions) ? $new_file_permissions : "0644" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["new_file_permissions_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["new_folder_permissions_title"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="new_folder_permissions" value="<?php echo isset($new_folder_permissions) ? $new_folder_permissions : "0755" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["new_folder_permissions_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
		  <tr class='row1'>
            <td colspan="2">
            </td>
          </tr>
        </table>
      </div>
	  
	  	      <!-- KCFinder settings -->
      <div class="tab-page" id="tabPage8">
        <h2 class="tab"><?php echo $_lang["settings_KC"] ?></h2>
        <script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage8" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["maxImageWidth"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="maxImageWidth" value="<?php echo isset($maxImageWidth) ? $maxImageWidth : "1600" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["maxImageWidth_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["maxImageHeight"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="maxImageHeight" value="<?php echo isset($maxImageHeight) ? $maxImageHeight : "1200" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["maxImageHeight_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["thumbWidth"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="thumbWidth" value="<?php echo isset($thumbWidth) ? $thumbWidth : "150" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["thumbWidth_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["thumbHeight"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="thumbHeight" value="<?php echo isset($thumbHeight) ? $thumbHeight : "150" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["thumbHeight_message"]?></td>
          </tr>

          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["thumbsDir"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 250px;" name="thumbsDir" value="<?php echo isset($thumbsDir) ? $thumbsDir : ".thumbs" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["thumbsDir_message"]?></td>
          </tr>
		  <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["jpegQuality"]?></b></td>
            <td>
              <input onchange="documentDirty=true;" type='text' maxlength='4' style="width: 50px;" name="jpegQuality" value="<?php echo isset($jpegQuality) ? $jpegQuality : "90" ; ?>" />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["jpegQuality_message"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
		  </tr>
          <tr>
               <td nowrap class="warning"><b><?php echo $_lang["denyZipDownload"] ?></b></td>
               <td> <input onchange="documentDirty=true;" type="radio" name="denyZipDownload" value="0" <?php echo $denyZipDownload=='0' ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["no"]?><br />
                 <input onchange="documentDirty=true;" type="radio" name="denyZipDownload" value="1" <?php echo (!isset($denyZipDownload) || $denyZipDownload=='1') ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["yes"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
		  </tr>
		  <tr>
               <td nowrap class="warning"><b><?php echo $_lang["denyExtensionRename"] ?></b></td>
               <td> <input onchange="documentDirty=true;" type="radio" name="denyExtensionRename" value="0" <?php echo $denyExtensionRename=='0' ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["no"]?><br />
                 <input onchange="documentDirty=true;" type="radio" name="denyExtensionRename" value="1" <?php echo (!isset($denyExtensionRename) || $denyExtensionRename=='1') ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["yes"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
		  </tr>
		  <tr>
               <td nowrap class="warning"><b><?php echo $_lang["showHiddenFiles"] ?></b></td>
               <td> <input onchange="documentDirty=true;" type="radio" name="showHiddenFiles" value="0" <?php echo $showHiddenFiles=='0' ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["no"]?><br />
                 <input onchange="documentDirty=true;" type="radio" name="showHiddenFiles" value="1" <?php echo (!isset($showHiddenFiles) || $showHiddenFiles=='1') ? 'checked="checked"' : ""; ?> />
                 <?php echo $_lang["yes"]?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
		  </tr>
		  
		  <tr class='row1'>
            <td colspan="2">
		        <?php
					// invoke OnMiscSettingsRender event
					$evtOut = $modx->invokeEvent("OnMiscSettingsRender");
					if(is_array($evtOut)) echo implode("",$evtOut);
		        ?>
            </td>
          </tr>
        </table>
      </div>
    </div>
</div>
</form>
<?php
/**
 * get_lang_keys
 * 
 * @return array of keys from a language file
 */
function get_lang_keys($filename) {
    $file = MODX_MANAGER_PATH.'includes/lang' . DIRECTORY_SEPARATOR . $filename;
    if(is_file($file) && is_readable($file)) {
        include($file);
        return array_keys($_lang);
    } else {
        return array();
    }
}
/**
 * get_langs_by_key
 * 
 * @return array of languages that define the key in their file
 */
function get_langs_by_key($key) {
    global $lang_keys;
    $lang_return = array();
    foreach($lang_keys as $lang=>$keys) {
        if(in_array($key, $keys)) {
            $lang_return[] = $lang;
        }
    }
    return $lang_return;
}

/**
 * get_lang_options
 *
 * returns html option list of languages
 * 
 * @param string $key specify language key to return options of langauges that override it, default return all languages
 * @param string $selected_lang specify language to select in option list, default none
 * @return html option list
 */
function get_lang_options($key=null, $selected_lang=null) {
    global $lang_keys, $_lang;
	$lang_options = '';
    if($key) {
        $languages = get_langs_by_key($key);
        sort($languages);
		$lang_options .= '<option value="">'.$_lang['language_title'].'</option>';

        foreach($languages as $language_name) {
            $uclanguage_name = ucwords(str_replace("_", " ", $language_name));
			$lang_options .= '<option value="'.$language_name.'">'.$uclanguage_name.'</option>';
        }
        return $lang_options;
    } else {
        $languages = array_keys($lang_keys);
        sort($languages);
        foreach($languages as $language_name) {
            $uclanguage_name = ucwords(str_replace("_", " ", $language_name));
            $sel = $language_name == $selected_lang ? ' selected="selected"' : '';
			$lang_options .= '<option value="'.$language_name.'" '.$sel.'>'.$uclanguage_name.'</option>';
        }
        return $lang_options;
    }
}

function form_radio($name,$value,$checked=false,$add='',$disabled=false) {
	if($checked)  $checked  = ' checked="checked"';
	if($disabled) $disabled = ' disabled';
	if($add)     $add = ' ' . $add;
	return '<input type="radio" name="' . $name . '" value="' . $value . '"' . $checked . $disabled . $add . ' />';
}

function wrap_label($str='',$object) {
	return "<label>{$object}\n{$str}</label>";
}

function parsePlaceholder($tpl='', $ph=array())
{
	if(empty($ph) || empty($tpl)) return $tpl;
	
	foreach($ph as $k=>$v)
	{
		$k = "[+{$k}+]";
		$tpl = str_replace($k, $v, $tpl);
	}
	return $tpl;
}