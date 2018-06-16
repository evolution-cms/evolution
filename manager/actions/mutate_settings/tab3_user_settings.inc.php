<!-- User settings -->
<div class="tab-page" id="tabPage4">
<h2 class="tab"><?php echo $_lang['settings_users'] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById( "tabPage4" ) );</script>
<table border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td nowrap class="warning"><?php echo $_lang['udperms_title'] ?><br><small>[(use_udperms)]</small></td>
    <td>
        <?php echo wrap_label($_lang['yes'],form_radio('use_udperms', 1, 'id="udPermsOn"'));?><br />
        <?php echo wrap_label($_lang['no'], form_radio('use_udperms', 0, 'id="udPermsOff"'));?>
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['udperms_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr class="udPerms" <?php echo showHide($use_udperms==1);?>>
    <td nowrap class="warning"><?php echo $_lang['udperms_allowroot_title'] ?><br><small>[(udperms_allowroot)]</small></td>
    <td>
      <label><input type="radio" name="udperms_allowroot" value="1" <?php echo $udperms_allowroot=='1' ? 'checked="checked"' : "" ; ?> />
      <?php echo $_lang['yes']?></label><br />
      <label><input type="radio" name="udperms_allowroot" value="0" <?php echo $udperms_allowroot=='0' ? 'checked="checked"' : "" ; ?> />
      <?php echo $_lang['no']?></label>
    </td>
  </tr>
  <tr class="udPerms" <?php echo showHide($use_udperms==1);?>>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['udperms_allowroot_message'] ?></td>
  </tr>

  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['email_sender_method'] ?><br><small>[(email_sender_method)]</small></td>
    <td>
      <label><input type="radio" name="email_sender_method" value="1" <?php echo $email_sender_method=='1' ? 'checked="checked"' : "" ; ?> />
      <?php echo $_lang['auto']?></label><br />
      <label><input type="radio" name="email_sender_method" value="0" <?php echo $email_sender_method=='0' ? 'checked="checked"' : "" ; ?> />
      <?php echo $_lang['use_emailsender']?></label>
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['email_sender_method_message'] ?></td>
  </tr>
  <!--for smtp-->

  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['email_method_title'] ?><br><small>[(email_method)]</small></td>
    <td>
        <?php echo wrap_label($_lang['email_method_mail'],form_radio('email_method','mail','id="useMail"'));?><br />
        <?php echo wrap_label($_lang['email_method_smtp'],form_radio('email_method','smtp','id="useSmtp"'));?>
        <div class="smtpRow" <?php echo showHide($email_method=='smtp');?>>
            <?php include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/snippet_smtp.inc.php'); ?>
        </div>
    </td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['emailsubject_title'] ?><br><small>[(emailsubject)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_emailsubject" id="reload_emailsubject_select" onchange="confirmLangChange(this, 'emailsubject_default', 'emailsubject_field');">
<?php echo get_lang_options('emailsubject_default');?>
      </select>
    </td>
    <td ><input id="emailsubject_field" name="emailsubject" onchange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" value="<?php echo $emailsubject; ?>" />
        <input type="hidden" name="emailsubject_default" id="emailsubject_default_hidden" value="<?php echo addslashes($_lang['emailsubject_default']);?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['emailsubject_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning" valign="top"><?php echo $_lang['signupemail_title'] ?><br><small>[(signupemail_message)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_signupemail_message" id="reload_signupemail_message_select" onchange="confirmLangChange(this, 'system_email_signup', 'signupemail_message_textarea');">
<?php echo get_lang_options('system_email_signup');?>
      </select>
    </td>
    <td> <textarea id="signupemail_message_textarea" name="signupemail_message" style="width:100%; height: 120px;"><?php echo $signupemail_message; ?></textarea>
        <input type="hidden" name="system_email_signup_default" id="system_email_signup_hidden" value="<?php echo addslashes($_lang['system_email_signup']);?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['signupemail_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning" valign="top"><?php echo $_lang['websignupemail_title'] ?><br><small>[(websignupemail_message)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_websignupemail_message" id="reload_websignupemail_message_select" onchange="confirmLangChange(this, 'system_email_websignup', 'websignupemail_message_textarea');">
<?php echo get_lang_options('system_email_websignup');?>
      </select>
    </td>
    <td> <textarea id="websignupemail_message_textarea" name="websignupemail_message" style="width:100%; height: 120px;"><?php echo $websignupemail_message; ?></textarea>
        <input type="hidden" name="system_email_websignup_default" id="system_email_websignup_hidden" value="<?php echo addslashes($_lang['system_email_websignup']);?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['websignupemail_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning" valign="top"><?php echo $_lang['webpwdreminder_title'] ?><br><small>[(webpwdreminder_message)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_system_email_webreminder_message" id="reload_system_email_webreminder_select" onchange="confirmLangChange(this, 'system_email_webreminder', 'system_email_webreminder_textarea');">
<?php echo get_lang_options('system_email_webreminder');?>
      </select>
    </td>
    <td> <textarea id="system_email_webreminder_textarea" name="webpwdreminder_message" style="width:100%; height: 120px;"><?php echo $webpwdreminder_message; ?></textarea>
        <input type="hidden" name="system_email_webreminder_default" id="system_email_webreminder_hidden" value="<?php echo addslashes($_lang['system_email_webreminder']);?>" />
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['webpwdreminder_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning" valign="top"><?php echo $_lang['allow_multiple_emails_title'] ?><br><small>[(allow_multiple_emails)]</small></td>
    <td>
      <?php echo wrap_label($_lang['yes'],form_radio('allow_multiple_emails','1'));?><br />
      <?php echo wrap_label($_lang['no'], form_radio('allow_multiple_emails','0'));?>
    </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['allow_multiple_emails_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2">
        <?php
            // invoke OnUserSettingsRender event
            $evtOut = $modx->invokeEvent('OnUserSettingsRender');
            if(is_array($evtOut)) echo implode("",$evtOut);
        ?>
    </td>
  </tr>
</table>
</div>
