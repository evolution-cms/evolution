<!-- Interface & editor settings -->
<div class="tab-page" id="tabPage5">
    <h2 class="tab"><?= $_lang['settings_ui'] ?></h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPage5'));</script>
    <table border="0" cellspacing="0" cellpadding="3">
        <tr>
            <td nowrap class="warning"><?= $_lang['language_title'] ?><br>
                <small>[(manager_language)]</small>
            </td>
            <td>
                <select name="manager_language" size="1" class="inputBox" onChange="documentDirty=true;">
                    <?= get_lang_options('', $manager_language) ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['language_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['charset_title'] ?><br>
                <small>[(modx_charset)]</small>
            </td>
            <td>
                <select name="modx_charset" size="1" class="inputBox" style="width:250px;" onChange="documentDirty=true;">
                    <?php include "charsets.php" ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['charset_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['manager_theme'] ?><br>
                <small>[(manager_theme)]</small>
            </td>
            <td>
                <select name="manager_theme" size="1" class="inputBox" onChange="documentDirty=true; document.forms['settings'].theme_refresher.value = Date.parse(new Date());">
                    <?php
                    $dir = dir("media/style/");
                    while ($file = $dir->read()) {
                        if ($file != "." && $file != ".." && is_dir("media/style/$file") && substr($file, 0, 1) != '.') {
                            if ($file === 'common') {
                                continue;
                            }
                            $themename = $file;
                            $selectedtext = $themename == $manager_theme ? "selected='selected'" : "";
                            echo "<option value='$themename' $selectedtext>" . ucwords(str_replace("_", " ", $themename)) . "</option>";
                        }
                    }
                    $dir->close();
                    ?>
                </select><input type="hidden" name="theme_refresher" value="" />
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        
        <tr>
            <td nowrap class="warning"><?= $_lang['manager_theme_mode'] ?><br>
                <small>[(manager_theme_mode)]</small>
            </td>
            <td>
                <label><input type="radio" name="manager_theme_mode" value="1" <?= $manager_theme_mode == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['manager_theme_mode1'] ?></label>
                <br />
                <label><input type="radio" name="manager_theme_mode" value="2" <?= $manager_theme_mode == '2' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['manager_theme_mode2'] ?></label>
                <br />
                <label><input type="radio" name="manager_theme_mode" value="3" <?= $manager_theme_mode == '3' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['manager_theme_mode3'] ?></label>
                <br />
                <label><input type="radio" name="manager_theme_mode" value="4" <?= ($manager_theme_mode == '4') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['manager_theme_mode4'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['manager_theme_mode_message'] ?></td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>


          <tr>
        <th><?php echo $_lang["login_logo_title"] ?><br><small>[(login_logo)]</small></th>
        <td>
          <div style="float:right;"><img name="login_logo" style="max-height: 48px" src="<?php echo !empty($login_logo) ? MODX_SITE_URL . $login_logo : $_style['tx']; ?>" /></div>
          <input name="login_logo" id="login_logo" type="text" maxlength="100" style="width: 200px;" value="<?php echo $login_logo; ?>" /><input type="button" value="<?php echo $_lang['insert']; ?>" onclick="BrowseServer('login_logo')" />
          <div class="comment"><?php echo $_lang["login_logo_message"] ?></div>
        </td>
      </tr>
       <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
      <tr>
        <th><?php echo $_lang["login_bg_title"] ?><br><small>[(login_bg)]</small></th>
        <td>
          <div style="float:right;"><img name="login_bg" style="max-height: 48px" src="<?php echo !empty($login_bg) ? MODX_SITE_URL . $login_bg : $_style['tx']; ?>" /></div>
          <input name="login_bg" id="login_bg" type="text" maxlength="100" style="width: 200px;" value="<?php echo $login_bg; ?>" /><input type="button" value="<?php echo $_lang['insert']; ?>" onclick="BrowseServer('login_bg')" />
          <div class="comment"><?php echo $_lang["login_bg_message"] ?></div>
        </td>
      </tr>
       <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
      <tr>
        <th><?php echo $_lang['login_form_position_title'] ?><br><small>[(login_form_position)]</small></th>
        <td>
            <?php echo wrap_label($_lang['login_form_position_left'],form_radio('login_form_position', 'left'));?><br />
            <?php echo wrap_label($_lang['login_form_position_center'], form_radio('login_form_position', 'center'));?><br />
            <?php echo wrap_label($_lang['login_form_position_right'], form_radio('login_form_position', 'right'));?>
        </td>
      </tr>
         <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>

      <tr>
        <th><?php echo $_lang['manager_menu_position_title'] ?><br><small>[(manager_menu_position)]</small></th>
        <td>
            <?php echo wrap_label($_lang['manager_menu_position_top'],form_radio('manager_menu_position', 'top'));?><br />
            <?php echo wrap_label($_lang['manager_menu_position_left'], form_radio('manager_menu_position', 'left'));?><br />
        </td>
      </tr>

        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>

        <tr>
            <td nowrap class="warning"><?= $_lang['show_picker'] ?><br>
                <small>[(show_picker)]</small>
            </td>
            <td>
                <label><input type="radio" name="show_picker" value="1" <?= $show_picker == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="show_picker" value="0" <?= ($show_picker == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['settings_show_picker_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>

        <tr>
            <td nowrap class="warning"><?= $_lang['warning_visibility'] ?><br>
                <small>[(warning_visibility)]</small>
            </td>
            <td>
                <label><input type="radio" name="warning_visibility" value="0" <?= $warning_visibility == '0' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['administrators'] ?></label>
                <br />
                <label><input type="radio" name="warning_visibility" value="1" <?= ($warning_visibility == '1') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['everybody'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['warning_visibility_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['tree_page_click'] ?><br>
                <small>[(tree_page_click)]</small>
            </td>
            <td>
                <label><input type="radio" name="tree_page_click" value="27" <?= $tree_page_click == '27' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['edit_resource'] ?></label>
                <br />
                <label><input type="radio" name="tree_page_click" value="3" <?= ($tree_page_click == '3') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['doc_data_title'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['tree_page_click_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['use_breadcrumbs'] ?><br>
                <small>[(use_breadcrumbs)]</small>
            </td>
            <td>
                <label><input type="radio" name="use_breadcrumbs" value="1" <?= $use_breadcrumbs == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="use_breadcrumbs" value="0" <?= ($use_breadcrumbs == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?>
                </label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['use_breadcrumbs_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['remember_last_tab'] ?><br>
                <small>[(remember_last_tab)]</small>
            </td>
            <td>
                <label><input type="radio" name="remember_last_tab" value="1" <?= $remember_last_tab == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="remember_last_tab" value="0" <?= ($remember_last_tab == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['remember_last_tab_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['use_global_tabs'] ?><br>
                <small>[(global_tabs)]</small>
            </td>
            <td>
                <label><input type="radio" name="global_tabs" value="1" <?= $global_tabs == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="global_tabs" value="0" <?= ($global_tabs == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td class="warning"><?= $_lang['group_tvs'] ?>
                <br>
                <small>[(group_tvs)]</small>
            </td>
            <td>
                <select name="group_tvs" size="1" class="form-control">
                    <?php
                    $tpl = '<option value="[+value+]" [+selected+]>[+title+]</option>' . "\n";
                    $option = explode(',', $_lang['settings_group_tv_options']);
                    $output = array();
                    foreach ($option as $k => $v) {
                        $selected = ($k == $group_tvs) ? 'selected' : '';
                        $s = array('[+value+]', '[+selected+]', '[+title+]');
                        $r = array($k, $selected, $v);
                        $t = array();
                        $output[] = str_replace($s, $r, $tpl);
                    }
                    echo implode("\n", $output)
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['settings_group_tv_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['show_newresource_btn'] ?><br>
                <small>[(show_newresource_btn)]</small>
            </td>
            <td>
                <label><input type="radio" name="show_newresource_btn" value="1" <?= $show_newresource_btn == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="show_newresource_btn" value="0" <?= ($show_newresource_btn == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['show_newresource_btn_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
                <tr>
            <td nowrap class="warning"><?= $_lang['show_fullscreen_btn'] ?><br>
                <small>[(show_fullscreen_btn)]</small>
            </td>
            <td>
                <label><input type="radio" name="show_fullscreen_btn" value="1" <?= $show_fullscreen_btn == '1' ? 'checked="checked"' : "" ?> />
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="show_fullscreen_btn" value="0" <?= ($show_fullscreen_btn == '0') ? 'checked="checked"' : "" ?> />
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['show_fullscreen_btn_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <th><?= $_lang['setting_resource_tree_node_name'] ?><br>
                <small>[(resource_tree_node_name)]</small>
            </th>
            <td>
                <select name="resource_tree_node_name" size="1" class="inputBox">
                    <?php
                    $tpl = '<option value="[+value+]" [+selected+]>[*[+value+]*]</option>' . "\n";
                    $option = array('pagetitle', 'longtitle', 'menutitle', 'alias', 'createdon', 'editedon', 'publishedon');
                    $output = array();
                    foreach ($option as $v) {
                        $selected = ($v == $resource_tree_node_name) ? 'selected' : '';
                        $s = array('[+value+]', '[+selected+]');
                        $r = array($v, $selected);
                        $output[] = str_replace($s, $r, $tpl);
                    }
                    echo implode("\n", $output)
                    ?>
                </select>
                <br />
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['setting_resource_tree_node_name_desc'] ?><br /><b><?= $_lang['setting_resource_tree_node_name_desc_add'] ?></b></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['session_timeout'] ?><br>
                <small>[(session_timeout)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="3" size="5" name="session_timeout" value="<?= $session_timeout ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['session_timeout_msg'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['tree_show_protected'] ?><br>
                <small>[(tree_show_protected)]</small>
            </td>
            <td>
                <label><input type="radio" name="tree_show_protected" value="1" <?= ($tree_show_protected == '1') ? 'checked="checked" ' : '' ?>/>
                    <?= $_lang['yes'] ?></label>
                <br />
                <label><input type="radio" name="tree_show_protected" value="0" <?= ($tree_show_protected == '0') ? 'checked="checked" ' : '' ?>/>
                    <?= $_lang['no'] ?></label>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['tree_show_protected_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['datepicker_offset'] ?><br>
                <small>[(datepicker_offset)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="50" size="5" name="datepicker_offset" value="<?= $datepicker_offset ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['datepicker_offset_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['datetime_format'] ?><br>
                <small>[(datetime_format)]</small>
            </td>
            <td>
                <select name="datetime_format" size="1" class="inputBox">
                    <?php
                    $datetime_format_list = array('dd-mm-YYYY', 'mm/dd/YYYY', 'YYYY/mm/dd');
                    $str = '';
                    foreach ($datetime_format_list as $value) {
                        $selectedtext = ($datetime_format == $value) ? ' selected' : '';
                        $str .= '<option value="' . $value . '"' . $selectedtext . '>';
                        $str .= $value . '</option>' . PHP_EOL;
                    }
                    echo $str;
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['datetime_format_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['nologentries_title'] ?><br>
                <small>[(number_of_logs)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_logs" value="<?= $number_of_logs ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['nologentries_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['mail_check_timeperiod_title'] ?><br>
                <small>[(mail_check_timeperiod)]</small>
            </td>
            <td><input type="text" name="mail_check_timeperiod" onChange="documentDirty=true;" size="5" value="<?= $mail_check_timeperiod ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['mail_check_timeperiod_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['nomessages_title'] ?><br>
                <small>[(number_of_messages)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_messages" value="<?= $number_of_messages ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['nomessages_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td nowrap class="warning"><?= $_lang['noresults_title'] ?><br>
                <small>[(number_of_results)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="50" size="5" name="number_of_results" value="<?= $number_of_results ?>" /></td>
        </tr>
        <tr>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['noresults_message'] ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <?php
        // invoke OnRichTextEditorRegister event
        $evtOut = $modx->invokeEvent('OnRichTextEditorRegister');
        if (!is_array($evtOut)) {
            $evtOut = array();
            $use_editor = 0;
        }
        ?>
        <tr <?= showHide(0 < count($evtOut)) ?>>
            <td nowrap class="warning"><?= $_lang['use_editor_title'] ?><br>
                <small>[(use_editor)]</small>
            </td>
            <td>
                <?= wrap_label($_lang['yes'], form_radio('use_editor', 1, 'id="editorRowOn"')) ?><br />
                <?= wrap_label($_lang['no'], form_radio('use_editor', 0, 'id="editorRowOff"')) ?>
            </td>
        </tr>
        <tr <?= showHide(0 < count($evtOut)) ?>>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['use_editor_message'] ?></td>
        </tr>
        <tr <?= showHide(0 < count($evtOut)) ?>>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td nowrap class="warning"><?= $_lang['which_editor_title'] ?><br>
                <small>[(which_editor)]</small>
            </td>
            <td>
                <select name="which_editor" onChange="documentDirty=true;">
                    <?php
                    // invoke OnRichTextEditorRegister event
                    echo "<option value='none'" . ($which_editor == 'none' ? " selected='selected'" : "") . ">" . $_lang['none'] . "</option>\n";
                    if (is_array($evtOut)) {
                        foreach ($evtOut as $editor) {
                            echo "<option value='$editor'" . ($which_editor == $editor ? " selected='selected'" : "") . ">$editor</option>\n";
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['which_editor_message'] ?></td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td nowrap class="warning"><?= $_lang['fe_editor_lang_title'] ?><br>
                <small>[(fe_editor_lang)]</small>
            </td>
            <td>
                <select name="fe_editor_lang" size="1" class="inputBox" onChange="documentDirty=true;">
                    <?= get_lang_options('', $fe_editor_lang) ?>
                </select>
            </td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['fe_editor_lang_message'] ?></td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td nowrap class="warning"><?= $_lang['editor_css_path_title'] ?><br>
                <small>[(editor_css_path)]</small>
            </td>
            <td><input onChange="documentDirty=true;" type="text" maxlength="255" style="width: 250px;" name="editor_css_path" value="<?= $editor_css_path ?>" /></td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td width="200">&nbsp;</td>
            <td class="comment"><?= $_lang['editor_css_path_message'] ?></td>
        </tr>
        <tr class="editorRow" <?= showHide($use_editor == 1) ?>>
            <td colspan="2">
                <div class="split"></div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <?php
                // invoke OnInterfaceSettingsRender event
                $evtOut = $modx->invokeEvent('OnInterfaceSettingsRender');
                if (is_array($evtOut)) {
                    echo implode("", $evtOut);
                }
                ?>
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
  var lastImageCtrl;
  var lastFileCtrl;
  function OpenServerBrowser(url, width, height) {
    var iLeft = (screen.width - width) / 2;
    var iTop = (screen.height - height) / 2;

    var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes";
    sOptions += ",width=" + width;
    sOptions += ",height=" + height;
    sOptions += ",left=" + iLeft;
    sOptions += ",top=" + iTop;

    var oWindow = window.open(url, "FCKBrowseWindow", sOptions);
  }

  function BrowseServer(ctrl) {
    lastImageCtrl = ctrl;
    var w = screen.width * 0.7;
    var h = screen.height * 0.7;
    OpenServerBrowser("<?php echo MODX_MANAGER_URL; ?>media/browser/<?php echo $which_browser;?>/browser.php?Type=images", w, h);
  }

  function BrowseFileServer(ctrl) {
    lastFileCtrl = ctrl;
    var w = screen.width * 0.7;
    var h = screen.height * 0.7;
    OpenServerBrowser("<?php echo MODX_MANAGER_URL; ?>media/browser/<?php echo $which_browser;?>/browser.php?Type=files", w, h);
  }

  function SetUrlChange(el) {
    if ('createEvent' in document) {
      var evt = document.createEvent('HTMLEvents');
      evt.initEvent('change', false, true);
      el.dispatchEvent(evt);
    } else {
      el.fireEvent('onchange');
    }
  }

  function SetUrl(url, width, height, alt) {
    if(lastFileCtrl) {
      var c = document.getElementById(lastFileCtrl);
      if(c && c.value != url) {
        c.value = url;
        SetUrlChange(c);
      }
      lastFileCtrl = '';
    } else if(lastImageCtrl) {
      var c = document.getElementById(lastImageCtrl);
      if(c && c.value != url) {
        c.value = url;
        SetUrlChange(c);
      }
      lastImageCtrl = '';
    } else {
      return;
    }
  }
</script>