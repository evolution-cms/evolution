<?php
  $site_unavailable_message_view = isset($site_unavailable_message) ? $site_unavailable_message : $_lang['siteunavailable_message_default'];
?>
<style>
table.sysSettings > tbody td, table.sysSettings > tbody th {border-bottom:1px dotted #ccc;padding:10px;}
table.sysSettings tr.noborder td {border:none;}
</style>
<!-- Site Settings -->
<div class="tab-page" id="tabPage2">
<h2 class="tab"><?php echo $_lang['settings_site'] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage2" ) );</script>
<table class="sysSettings" border="0" cellspacing="0" cellpadding="3">
  <tr>
    <th><?php echo $_lang['sitestatus_title'] ?><br><small>[(site_status)]</small></th>
    <td>
        <?php echo wrap_label($_lang['online'],  form_radio('site_status', 1));?><br />
        <?php echo wrap_label($_lang['offline'], form_radio('site_status', 0));?>
    </td>
  </tr>
  <tr>
      <th><?php echo $modx->htmlspecialchars($_lang['sitename_title']) ?><br><small>[(site_name)]</small></th>
      <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 200px;" name="site_name" value="<?php echo $modx->htmlspecialchars($site_name); ?>" />
  <div class="comment"><?php echo $_lang['sitename_message'] ?></div>
  </td>
    </tr>
  <tr>
    <th><?php echo $_lang['emailsender_title'] ?><br><small>[(emailsender)]</small></th>
    <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="emailsender" value="<?php echo $emailsender; ?>" />
  <div class="comment"><?php echo $_lang['emailsender_message'] ?></div>
  </td>
  </tr>
  <tr>
    <th><?php echo $_lang['sitestart_title'] ?><br><small>[(site_start)]</small></th>
    <td><input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="site_start" value="<?php echo $site_start; ?>" />
  <div class="comment"><?php echo $_lang['sitestart_message'] ?></div>
  </td>
  </tr>
  <tr>
    <th><?php echo $_lang['errorpage_title'] ?><br><small>[(error_page)]</small></th>
    <td><input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="error_page" value="<?php echo $error_page; ?>" />
  <div class="comment"><?php echo $_lang['errorpage_message'] ?></div>
  </td>
  </tr>
  <tr>
    <th><?php echo $_lang['unauthorizedpage_title'] ?><br><small>[(unauthorized_page)]</small></th>
    <td><input onchange="documentDirty=true;" type="text" maxlength="10" size="5" name="unauthorized_page" value="<?php echo $unauthorized_page; ?>" />
  <div class="comment"><?php echo $_lang['unauthorizedpage_message'] ?></div>
  </td>
  </tr>
  <tr>
    <th><?php echo $_lang['siteunavailable_page_title'] ?><br><small>[(site_unavailable_page)]</small></th>
    <td><input onchange="documentDirty=true;" name="site_unavailable_page" type="text" maxlength="10" size="5" value="<?php echo $site_unavailable_page; ?>" />
  <div class="comment"><?php echo $_lang['siteunavailable_page_message'] ?></div>
  </td>
  </tr>
  <tr>
    <th><?php echo $_lang['siteunavailable_title'] ?><br><small>[(site_unavailable_message)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_site_unavailable" id="reload_site_unavailable_select" onchange="confirmLangChange(this, 'siteunavailable_message_default', 'site_unavailable_message_textarea');">
<?php echo get_lang_options('siteunavailable_message_default');?>
      </select>
    </th>
    <td> <textarea name="site_unavailable_message" id="site_unavailable_message_textarea" style="width:100%; height: 120px;"><?php echo $site_unavailable_message_view; ?></textarea>
        <input type="hidden" name="siteunavailable_message_default" id="siteunavailable_message_default_hidden" value="<?php echo addslashes($_lang['siteunavailable_message_default']);?>" />
  <div class="comment"><?php echo $_lang['siteunavailable_message'];?></div>
    </td>
  </tr>
  <tr>
    <th><?php echo $_lang['defaulttemplate_title'] ?><br><small>[(default_template)]</small></th>
    <td>
    <?php
        $rs = $modx->db->select(
            't.templatename, t.id, c.category',
            $modx->getFullTableName('site_templates')." AS t
                LEFT JOIN ".$modx->getFullTableName('categories')." AS c ON t.category = c.id",
            "",
            'c.category, t.templatename ASC'
            );
    ?>
      <select name="default_template" class="inputBox" onchange="documentDirty=true;wrap=document.getElementById('template_reset_options_wrapper');if(this.options[this.selectedIndex].value != '<?php echo $default_template;?>'){wrap.style.display='block';}else{wrap.style.display='none';}" style="width:150px">
        <?php

        $currentCategory = '';
                        while ($row = $modx->db->getRow($rs)) {
            $thisCategory = $row['category'];
            if($thisCategory == null) {
                $thisCategory = $_lang['no_category'];
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
            <label><input type="radio" name="reset_template" value="1" /> <?php echo $_lang['template_reset_all']; ?></label><br />
            <label><input type="radio" name="reset_template" value="2" /> <?php echo sprintf($_lang['template_reset_specific'],$oldTmpName); ?></label>
        </div>
        <input type="hidden" name="old_template" value="<?php echo $oldTmpId; ?>" />
  <div class="comment"><?php echo $_lang['defaulttemplate_message'] ?></div>
    </td>
  </tr>
  <tr>
        <th><?php echo $_lang['defaulttemplate_logic_title'];?><br><small>[(auto_template_logic)]</small></th>
        <td>
            <p><?php echo $_lang['defaulttemplate_logic_general_message'];?></p>
            <label><input type="radio" name="auto_template_logic" value="system"<?php if($auto_template_logic == 'system') {echo " checked='checked'";}?>/> <?php echo $_lang['defaulttemplate_logic_system_message']; ?></label><br />
            <label><input type="radio" name="auto_template_logic" value="parent"<?php if($auto_template_logic == 'parent') {echo " checked='checked'";}?>/> <?php echo $_lang['defaulttemplate_logic_parent_message']; ?></label><br />
            <label><input type="radio" name="auto_template_logic" value="sibling"<?php if($auto_template_logic == 'sibling') {echo " checked='checked'";}?>/> <?php echo $_lang['defaulttemplate_logic_sibling_message']; ?></label><br />
        </td>
    </tr>
    <tr>
      <th><?php echo $_lang['chunk_processor'] ?><br><small>[(chunk_processor)]</small></th>
      <td >
          <?php echo wrap_label('DocumentParser', form_radio('chunk_processor', ''));?><br />
          <?php echo wrap_label('DLTemplate',  form_radio('chunk_processor', 'DLTemplate'));?>
      </td>
    </tr>
    <tr>
      <th><?php echo $_lang['enable_mootools'] ?><br><small>[(enable_mootools)]</small></th>
      <td>
        <?php echo wrap_label($_lang['yes'],form_radio('enable_mootools', 1));?><br />
        <?php echo wrap_label($_lang['no'], form_radio('enable_mootools', 0));?>
        <div class="comment">
            <?php echo $_lang['enable_mootools_message']; ?>          
        </div>
      </td>
      </tr>
    <tr>
    <tr>
      <th><?php echo $_lang['enable_at_syntax_title'] ?><br><small>[(enable_at_syntax)]</small></th>
      <td >
          <?php echo wrap_label($_lang['yes'],form_radio('enable_at_syntax', 1));?><br />
          <?php echo wrap_label($_lang['no'], form_radio('enable_at_syntax', 0));?>
        <div class="comment">
            <?php echo $_lang['enable_at_syntax_message']; ?>
          <ul>
            <li><a href="https://github.com/modxcms/evolution/wiki/@IF-@ELSEIF-@ELSE-@ENDIF" target="_blank">@IF @ELSEIF @ELSE @ENDIF</a></li>
            <li>&lt;@LITERAL&gt; {{string}} [*string*] [[string]] &lt;@ENDLITERAL&gt;</li>
            <li>&lt;!--@- Do not output -@--&gt;</li>
          </ul>
        </div>
      </td>
    </tr>
    <tr>
      <th><?php echo $_lang['enable_filter_title'] ?><br><small>[(enable_filter)]</small></th>
      <td >
        <?php
            // Check if PHX is enabled
            $count = $modx->db->getRecordCount(
              $modx->db->select('id', '[+prefix+]site_plugins',
              "plugincode LIKE '%phx.parser.class.inc.php%OnParseDocument();%' AND disabled != 1")
            );
            if($count) {
                $disabledFilters = 1;
                echo '<b>'.$_lang['enable_filter_phx_warning'].'</b><br/>';
            }
            else $disabledFilters = false;
        ?>
        <?php echo wrap_label($_lang['yes'],form_radio('enable_filter', 1, '', $disabledFilters));?><br />
        <?php echo wrap_label($_lang['no'], form_radio('enable_filter', 0, '', $disabledFilters));?>
        <div class="comment"><?php echo $_lang['enable_filter_message']; ?></div>
      </td>
    </tr>
  <tr>
    <th><?php echo $_lang['defaultpublish_title'] ?><br><small>[(publish_default)]</small></th>
    <td>
        <?php echo wrap_label($_lang['yes'],form_radio('publish_default', 1));?><br />
        <?php echo wrap_label($_lang['no'],form_radio('publish_default', 0));?>
        <div class="comment"><?php echo $_lang['defaultpublish_message'] ?></div>
    </td>
  </tr>

  <tr>
    <th><?php echo $_lang['defaultcache_title'] ?><br><small>[(cache_default)]</small></th>
    <td>
        <?php echo wrap_label($_lang['yes'],form_radio('cache_default', 1));?><br />
        <?php echo wrap_label($_lang['no'],form_radio('cache_default', 0));?>
        <div class="comment"><?php echo $_lang['defaultcache_message'] ?></div>
    </td>
  </tr>
  <tr>
    <th><?php echo $_lang['defaultsearch_title'] ?><br><small>[(search_default)]</small></th>
    <td>
        <?php echo wrap_label($_lang['yes'],form_radio('search_default', 1));?><br />
        <?php echo wrap_label($_lang['no'],form_radio('search_default', 0));?>
        <div class="comment"><?php echo $_lang['defaultsearch_message'] ?></div>
    </td>
  </tr>
  <tr>
    <th><?php echo $_lang['defaultmenuindex_title'] ?><br><small>[(auto_menuindex)]</small></th>
    <td>
        <?php echo wrap_label($_lang['yes'],form_radio('auto_menuindex', 1));?><br />
        <?php echo wrap_label($_lang['no'],form_radio('auto_menuindex', 0));?>
        <div class="comment"><?php echo $_lang['defaultmenuindex_message'] ?></div>
    </td>
  </tr>
  <tr>
    <th><?php echo $_lang['custom_contenttype_title'] ?></th>
    <td><input name="txt_custom_contenttype" type="text" maxlength="100" style="width: 200px;" value="" /><input type="button" value="<?php echo $_lang['add']; ?>" onclick='addContentType()' /><br />
    <table border="0" cellspacing="0" cellpadding="0"><tr><td valign="top">
    <select name="lst_custom_contenttype" style="width:200px;height:100px;" size="5">
    <?php
        $ct = explode(",",$custom_contenttype);
        for($i=0;$i<count($ct);$i++) {
            echo "<option value=\"".$ct[$i]."\">".$ct[$i]."</option>";
        }
    ?>
    </select>
    <input name="custom_contenttype" type="hidden" value="<?php echo $custom_contenttype; ?>" />
    </td><td valign="top">&nbsp;<input name="removecontenttype" type="button" value="<?php echo $_lang['remove']; ?>" onclick='removeContentType()' /></td></tr></table>
    <div class="comment"><?php echo $_lang['custom_contenttype_message'] ?></div>
    </td>
  </tr>
  <tr>
<th><?php echo $_lang['docid_incrmnt_method_title'] ?><br><small>[(docid_incrmnt_method)]</small></th>
<td>
<label><input type="radio" name="docid_incrmnt_method" value="0"
    <?php echo ($docid_incrmnt_method=='0') ? 'checked="checked"' : "" ; ?> />
    <?php echo $_lang['docid_incrmnt_method_0']?></label><br />

<label><input type="radio" name="docid_incrmnt_method" value="1"
    <?php echo ($docid_incrmnt_method=='1') ? 'checked="checked"' : "" ; ?> />
    <?php echo $_lang['docid_incrmnt_method_1']?></label><br />
<label><input type="radio" name="docid_incrmnt_method" value="2"
    <?php echo ($docid_incrmnt_method=='2') ? 'checked="checked"' : "" ; ?> />
    <?php echo $_lang['docid_incrmnt_method_2']?></label><br />
</td>
</tr>

<tr>
<th><?php echo $_lang['enable_cache_title'] ?><br><small>[(enable_cache)]</small></th>
<td>
    <?php echo wrap_label($_lang['enabled'],form_radio('enable_cache', 1));?><br />
    <?php echo wrap_label($_lang['disabled'], form_radio('enable_cache', 0));?><br />
    <?php echo wrap_label($_lang['disabled_at_login'], form_radio('enable_cache', 2));?>
</td>
</tr>

<tr>
<th><?php echo $_lang['cache_type_title'] ?><br><small>[(cache_type)]</small></th>
<td>
<?php echo wrap_label($_lang['cache_type_1'],form_radio('cache_type', 1));?><br />
<?php echo wrap_label($_lang['cache_type_2'], form_radio('cache_type', 2));?>
</td>
</tr>
  <tr>
<th><?php echo $_lang['minifyphp_incache_title'] ?><br><small>[(minifyphp_incache)]</small></th>
    <td>
<?php echo wrap_label($_lang['enabled'],form_radio('minifyphp_incache', 1));?><br />
<?php echo wrap_label($_lang['disabled'], form_radio('minifyphp_incache', 0));?>
<div class="comment"><?php echo $_lang['minifyphp_incache_message'] ?></div>
    </td>
  </tr>

    <tr>
      <th><?php echo $_lang['serveroffset_title'] ?><br><small>[(server_offset_time)]</small></th>
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
        </select>
        <div class="comment"><?php printf($_lang['serveroffset_message'], strftime('%H:%M:%S', time()), strftime('%H:%M:%S', time()+$server_offset_time)); ?></div>
        </td>
    </tr>
    <tr>
      <th><?php echo $_lang['server_protocol_title'] ?><br><small>[(server_protocol)]</small></th>
      <td>
        <?php echo wrap_label($_lang['server_protocol_http'],form_radio('server_protocol', 'http'));?><br />
        <?php echo wrap_label($_lang['server_protocol_https'], form_radio('server_protocol', 'https'));?>
        <div class="comment"><?php echo $_lang['server_protocol_message'] ?></div>
      </td>
    </tr>
    <tr>
      <th><?php echo $_lang['rss_url_news_title'] ?><br><small>[(rss_url_news)]</small></th>
      <td ><input onchange="documentDirty=true;" type="text" maxlength="350" style="width: 350px;" name="rss_url_news" value="<?php echo $rss_url_news; ?>" />
        <div class="comment"><?php echo $_lang['rss_url_news_message'] ?></div>
      </td>
    </tr>
    <tr>
     <th><?php echo $_lang['track_visitors_title'] ?><br><small>[(track_visitors)]</small></th>
     <td>
         <?php echo wrap_label($_lang['yes'],form_radio('track_visitors', 1));?><br />
         <?php echo wrap_label($_lang['no'],form_radio('track_visitors', 0));?>
         <div class="comment"><?php echo $_lang['track_visitors_message'] ?></div>
     </td>
   </tr>



  <tr>
    <td colspan="2" style="border:none;">
        <?php
            // invoke OnSiteSettingsRender event
            $evtOut = $modx->invokeEvent('OnSiteSettingsRender');
            if(is_array($evtOut)) echo implode("",$evtOut);
        ?>
    </td>
  </tr>
</table>

</div>
