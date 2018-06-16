<!-- Miscellaneous settings -->
<div class="tab-page" id="tabPage7">
<h2 class="tab"><?php echo $_lang['settings_misc'] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage7" ) );</script>
<table border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td nowrap class="warning"><?php echo $_lang['filemanager_path_title']; ?><br><small>[(filemanager_path)]</small></td>
    <td>
      <?php echo $_lang['default']; ?> <span id="default_filemanager_path">[(base_path)]</span><br />
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="filemanager_path" id="filemanager_path" value="<?php echo $filemanager_path; ?>" /> <input type="button" onclick="reset_path('filemanager_path');" value="<?php echo $_lang['reset']; ?>" name="reset_filemanager_path">
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['filemanager_path_message']?></td>
  </tr>
  <tr>
  <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['uploadable_files_title']?><br><small>[(upload_files)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="upload_files" value="<?php echo $upload_files; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['uploadable_files_message']?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['uploadable_images_title']?><br><small>[(upload_images)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="upload_images" value="<?php echo $upload_images; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['uploadable_images_message']?></td>
  </tr>
  <tr>
  <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['uploadable_media_title']?><br><small>[(upload_media)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="upload_media" value="<?php echo $upload_media; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['uploadable_media_message']?></td>
  </tr>
  <tr>
  <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['uploadable_flash_title']?><br><small>[(upload_flash)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="upload_flash" value="<?php echo $upload_flash; ?>" />
    </td>
  </tr>
    <tr>
      <td width="200">&nbsp;</td>
      <td class="comment"><?php echo $_lang['uploadable_flash_message']?></td>
    </tr>
    <tr>
      <td colspan="2"><div class="split"></div></td>
    </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['upload_maxsize_title']?><br><small>[(upload_maxsize)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="upload_maxsize" value="<?php echo $upload_maxsize; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['upload_maxsize_message']?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['new_file_permissions_title']?><br><small>[(new_file_permissions)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="4" style="width: 50px;" name="new_file_permissions" value="<?php echo $new_file_permissions; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['new_file_permissions_message']?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['new_folder_permissions_title']?><br><small>[(new_folder_permissions)]</small></td>
    <td>
      <input onchange="documentDirty=true;" type="text" maxlength="4" style="width: 50px;" name="new_folder_permissions" value="<?php echo $new_folder_permissions; ?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['new_folder_permissions_message']?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td colspan="2">
        <?php
            // invoke OnMiscSettingsRender event
            $evtOut = $modx->invokeEvent('OnFileManagerSettingsRender');
            if(is_array($evtOut)) echo implode("",$evtOut);
        ?>
    </td>
  </tr>
</table>
</div>

