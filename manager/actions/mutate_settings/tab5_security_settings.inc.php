<?php
    $MODX_SITE_HOSTNAMES = MODX_SITE_HOSTNAMES; // Fix for PHP 5.4
    if(empty($valid_hostnames) && empty($MODX_SITE_HOSTNAMES)) {
        $valid_hostnames = $_SERVER['HTTP_HOST'];
    } else {
        $valid_hostnames = $MODX_SITE_HOSTNAMES;
    }
?>
<!-- Interface & editor settings -->
<div class="tab-page" id="tabPageSecurity">
<h2 class="tab"><?php echo $_lang['settings_security'] ?></h2>
<script type="text/javascript">tpSettings.addTabPage( document.getElementById("tabPageSecurity") );</script>
<table border="0" cellspacing="0" cellpadding="3">

<tr>
<th><?php echo $_lang['allow_eval_title']; ?><br><small>[(allow_eval)]</small></th>
<td>
<?php echo wrap_label($_lang['allow_eval_with_scan']         , form_radio('allow_eval','with_scan'));?><br />
<?php echo wrap_label($_lang['allow_eval_with_scan_at_post'] , form_radio('allow_eval','with_scan_at_post'));?><br />
<?php echo wrap_label($_lang['allow_eval_everytime_eval']    , form_radio('allow_eval','everytime_eval'));?><br />
<?php echo wrap_label($_lang['allow_eval_dont_eval']         , form_radio('allow_eval','dont_eval'));?>
    <div class="comment">
        <?php echo $_lang['allow_eval_msg'] ?>
    </div>
</td>
</tr>
<tr>
  <td colspan="2"><div class="split"></div></td>
</tr>
<tr>
  <td nowrap class="warning"><?php echo $_lang['safe_functions_at_eval_title'] ?><br><small>[(safe_functions_at_eval)]</small></td>
  <td >
    <input onchange="documentDirty=true;" type="text" style="width: 350px;" name="safe_functions_at_eval" value="<?php echo $safe_functions_at_eval; ?>" />
    <div class="comment">
        <?php echo $_lang['safe_functions_at_eval_msg'] ?>
    </div>
  </td>
</tr>
<tr>
  <td colspan="2"><div class="split"></div></td>
</tr>

<tr>
    <th><?php echo $_lang['check_files_onlogin_title'] ?><br><small>[(check_files_onlogin)]</small></th>
    <td>
      <textarea name="check_files_onlogin"><?php echo $check_files_onlogin;?></textarea><br />

</td>
</tr>
   <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
    <tr>
      <td nowrap class="warning"><?php echo $_lang['validate_referer_title'] ?><br><small>[(validate_referer)]</small></td>
      <td>
        <?php echo wrap_label($_lang['yes'],form_radio('validate_referer', 1));?><br />
        <?php echo wrap_label($_lang['no'], form_radio('validate_referer', 0));?>
      </td>
    </tr>
    <tr>
      <td width="200">&nbsp;</td>
      <td class="comment"><?php echo $_lang['validate_referer_message'] ?></td>
    </tr>
    <tr>
      <td colspan="2"><div class="split"></div></td>
    </tr>
    <tr>
      <td nowrap class="warning"><?php echo $_lang['valid_hostnames_title'] ?><br><small>[(valid_hostnames)]</small></td>
      <td ><input onchange="documentDirty=true;" type="text" maxlength="255" style="width: 200px;" name="valid_hostnames" value="<?php echo $modx->htmlspecialchars($valid_hostnames); ?>" /></td>
    </tr>
    <tr>
      <td width="200">&nbsp;</td>
      <td class="comment"><?php echo $_lang['valid_hostnames_message'] ?></td>
    </tr>
    <tr>
      <td colspan="2"><div class="split"></div></td>
    </tr>
    <tr>
      <td nowrap class="warning"><?php echo $_lang['rss_url_security_title'] ?><br><small>[(rss_url_security)]</small></td>
      <td ><input onchange="documentDirty=true;" type="text" maxlength="350" style="width: 350px;" name="rss_url_security" value="<?php echo $rss_url_security; ?>" /></td>
    </tr>
    <tr>
      <td width="200">&nbsp;</td>
      <td class="comment"><?php echo $_lang['rss_url_security_message'] ?></td>
    </tr>
    <tr>
      <td colspan="2"><div class="split"></div></td>
    </tr>
  <tr>
<th><?php echo $_lang['a17_error_reporting_title']; ?><br><small>[(error_reporting)]</small></th>
<td>
<?php echo wrap_label($_lang['a17_error_reporting_opt0'], form_radio('error_reporting','0'));?><br />
<?php echo wrap_label($_lang['a17_error_reporting_opt1'], form_radio('error_reporting','1'));?><br />
<?php echo wrap_label($_lang['a17_error_reporting_opt2'], form_radio('error_reporting','2'));?><br />
<?php echo wrap_label($_lang['a17_error_reporting_opt99'],form_radio('error_reporting','99'));?>
</td>
</tr>
<tr>
    <td width="200">&nbsp;</td>
    <td class="comment"> <?php echo $_lang['a17_error_reporting_msg'];?></td>
</tr>
<tr><td colspan="2"><div class="split"></div></td></tr>
<tr>
    <th><?php echo $_lang['mutate_settings.dynamic.php6']; ?><br><small>[(send_errormail)]</small></th>
    <td>
        <?php echo wrap_label($_lang['mutate_settings.dynamic.php7'],form_radio('send_errormail','0'));?><br />
        <?php echo wrap_label('error',form_radio('send_errormail','3'));?><br />
        <?php echo wrap_label('error + warning',form_radio('send_errormail','2'));?><br />
        <?php echo wrap_label('error + warning + information',form_radio('send_errormail','1'));?>
    </td>
</tr>
    <tr>
        <td width="200">&nbsp;</td>
        <td class="comment">
            <?php echo parseText($_lang['mutate_settings.dynamic.php8'],array('emailsender'=>$modx->config['emailsender']));?>
        </td>
    </tr>
   <tr>
    <td colspan="2"><div class="split"></div></td>
</tr>
<tr>
<th><?php echo $_lang['enable_bindings_title'] ?><br><small>[(enable_bindings)]</small></th>
<td>
<?php echo wrap_label($_lang['yes'],form_radio('enable_bindings','1'));?><br />
<?php echo wrap_label($_lang['no'], form_radio('enable_bindings','0'));?>

</td>
</tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment">  <?php echo $_lang['enable_bindings_message'] ?></td>
</tr>
<tr>
    <td width="200">&nbsp;</td>
    <td class="comment">  <?php echo $_lang['check_files_onlogin_message'] ?></td>
</tr>
<tr>
  <td colspan="2"><div class="split"></div></td>
</tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['failed_login_title'] ?><br><small>[(failed_login_attempts)]</small></td>
    <td><input type="text" name="failed_login_attempts" style="width:50px" value="<?php echo $failed_login_attempts; ?>" /></td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['failed_login_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['blocked_minutes_title'] ?><br><small>[(blocked_minutes)]</small></td>
    <td><input type="text" name="blocked_minutes" style="width:100px" value="<?php echo $blocked_minutes; ?>" /></td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['blocked_minutes_message'] ?></td>
  </tr>
<tr>
  <td colspan="2"><div class="split"></div></td>
</tr>
<tr>
<th><?php echo $_lang['pwd_hash_algo_title'] ?><br><small>[(pwd_hash_algo)]</small></th>
<td>
<?php
if(empty($pwd_hash_algo)) $phm['sel']['UNCRYPT'] = 1;
$phm['e']['BLOWFISH_Y'] = $modx->manager->checkHashAlgorithm('BLOWFISH_Y') ? 0:1;
$phm['e']['BLOWFISH_A'] = $modx->manager->checkHashAlgorithm('BLOWFISH_A') ? 0:1;
$phm['e']['SHA512']     = $modx->manager->checkHashAlgorithm('SHA512') ? 0:1;
$phm['e']['SHA256']     = $modx->manager->checkHashAlgorithm('SHA256') ? 0:1;
$phm['e']['MD5']        = $modx->manager->checkHashAlgorithm('MD5') ? 0:1;
$phm['e']['UNCRYPT']    = $modx->manager->checkHashAlgorithm('UNCRYPT') ? 0:1;
?>
<?php echo wrap_label('CRYPT_BLOWFISH_Y (salt &amp; stretch)',form_radio('pwd_hash_algo','BLOWFISH_Y', '', $phm['e']['BLOWFISH_Y']));?><br />
<?php echo wrap_label('CRYPT_BLOWFISH_A (salt &amp; stretch)',form_radio('pwd_hash_algo','BLOWFISH_A', '', $phm['e']['BLOWFISH_A']));?><br />
<?php echo wrap_label('CRYPT_SHA512 (salt &amp; stretch)'    ,form_radio('pwd_hash_algo','SHA512'    , '', $phm['e']['SHA512']));?><br />
<?php echo wrap_label('CRYPT_SHA256 (salt &amp; stretch)'    ,form_radio('pwd_hash_algo','SHA256'    , '', $phm['e']['SHA256']));?><br />
<?php echo wrap_label('CRYPT_MD5 (salt &amp; stretch)'       ,form_radio('pwd_hash_algo','MD5'       , '', $phm['e']['MD5']));?><br />
<?php echo wrap_label('UNCRYPT(32 chars salt + SHA-1 hash)'  ,form_radio('pwd_hash_algo','UNCRYPT'  , '', $phm['e']['UNCRYPT']));?>
</td>
</tr>
<tr>
    <td width="200">&nbsp;</td>
    <td class="comment">  <?php echo $_lang['pwd_hash_algo_message']?></td>
</tr>
   <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <?php
      // Check for GD before allowing captcha to be enabled
      $gdAvailable = extension_loaded('gd');
  ?>
<?php
$gdAvailable = extension_loaded('gd');
if(!$gdAvailable) $use_captcha = 0;
?>
  <tr>
    <td nowrap class="warning"><?php echo $_lang['captcha_title'] ?><br><small>[(use_captcha)]</small></td>
    <td> <label><input type="radio" id="captchaOn" name="use_captcha" value="1" <?php echo ($use_captcha==1) ? 'checked="checked"' : "" ; echo (!$gdAvailable)? ' readonly="readonly"' : ''; ?> />
      <?php echo $_lang['yes']?></label><br />
      <label><input type="radio" id="captchaOff" name="use_captcha" value="0" <?php echo ($use_captcha==0) ? 'checked="checked"' : "" ; echo (!$gdAvailable)? ' readonly="readonly"' : '';?> />
      <?php echo $_lang['no']?></label> </td>
  </tr>
  <tr>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['captcha_message'] ?></td>
  </tr>
  <tr>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr class="captchaRow" <?php echo showHide($use_captcha==1);?>>
    <td nowrap class="warning"><?php echo $_lang['captcha_words_title'] ?><br><small>[(captcha_words)]</small>
      <br />
      <p><?php echo $_lang['update_settings_from_language']; ?></p>
      <select name="reload_captcha_words" id="reload_captcha_words_select" onchange="confirmLangChange(this, 'captcha_words_default', 'captcha_words_input');">
<?php echo get_lang_options('captcha_words_default');?>
      </select>
    </td>
    <td><input type="text" id="captcha_words_input" name="captcha_words" style="width:250px" value="<?php echo $captcha_words; ?>" />
        <input type="hidden" name="captcha_words_default" id="captcha_words_default_hidden" value="<?php echo addslashes($_lang['captcha_words_default']);?>" />
    </td>
  </tr>
  <tr class="captchaRow" <?php echo showHide($use_captcha==1);?>>
    <td width="200">&nbsp;</td>
    <td class="comment"><?php echo $_lang['captcha_words_message'] ?></td>
  </tr>
  <tr class="captchaRow" <?php echo showHide($use_captcha==1);?>>
    <td colspan="2"><div class="split"></div></td>
  </tr>
  <tr>
    <td colspan="2">
        <?php
            // invoke OnMiscSettingsRender event
            $evtOut = $modx->invokeEvent('OnSecuritySettingsRender');
            if(is_array($evtOut)) echo implode("",$evtOut);
        ?>
    </td>
  </tr>
</table>
</div>
