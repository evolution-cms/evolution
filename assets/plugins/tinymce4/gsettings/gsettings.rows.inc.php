<?php
$params = isset($params) && !empty($params) ? $params : array('base_url'=>'', 'skinsDirectory'=>'', 'skinthemeDirectory'=>'');
// Hold general settings based on old Modx TinyMCE-Settings
// Settings interface rows configuration
$settingsRows = array(
    'theme'=>array(
        'title'=>'editor_theme_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <select name="[+name+]" id="[+name+]" class="inputBox" size="1">
                [+theme_options+]
            </select>',
        'message'=>'editor_theme_message',
        'messageVal'=>'<b>'. $params['base_url'].'<u>theme</u></b>'
    ),
    'skin'=>array(
        'title'=>'editor_skin_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <select name="[+name+]" id="[+name+]" class="inputBox" size="1">
                [+skin_options+]
            </select>',
        'message'=>'editor_skin_message',
        'messageVal'=>'<b>'. $params['base_url'].'<u>'.$params['skinsDirectory'] .'</u></b>'
    ),
    'skintheme'=>array(
        'title'=>'editor_skintheme_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <select name="[+name+]" id="[+name+]" class="inputBox" size="1">
                [+skintheme_options+]
            </select>',
        'message'=>'editor_skintheme_message',
        'messageVal'=>'<b>'. $params['base_url'].'<u>'.$params['skinthemeDirectory'] .'</u></b>'
    ),
    'template'=>array(
        'title'=>'tpl_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <label>
                [+tpl_docid+]
                <input type="text" name="[+name+]_docs" value="[+[+name+]_docs+]" id="[+name+]" class="inputBox" />
            </label>
            <label>
                [+tpl_chunkname+]
                <input type="text" name="[+name+]_chunks" value="[+[+name+]_chunks+]" id="[+name+]" class="inputBox" />
            </label>',
        'message'=>'tpl_msg'
    ),
    'entermode'=>array(
        'title'=>'editor_entermode_title',
        'name'=>'[+name+]',
        'configTpl'=>'[+entermode_options+]',
        'message'=>'editor_entermode_message'
    ),
    'element_format'=>array(
        'title'=>'element_format_title',
        'name'=>'[+name+]',
        'configTpl'=>'[+element_format_options+]',
        'message'=>'element_format_message'
    ),
    'schema'=>array(
        'title'=>'schema_title',
        'name'=>'[+name+]',
        'configTpl'=>'[+schema_options+]',
        'message'=>'schema_message'
    ),
    'custom_plugins'=>array(
        'title'=>'editor_custom_plugins_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <textarea name="[+name+]" id="[+name+]" class="inputBox mce" >[+[+editorKey+]_custom_plugins+]</textarea>',
        'message'=>'editor_custom_plugins_message',
        'defaultCheckbox'=>true
    ),
    'custom_buttons'=>array(
        'title'=>'editor_custom_buttons_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            #1: <textarea name="[+name+]1" id="[+name+]" class="inputBox mce">[+[+editorKey+]_custom_buttons1+]</textarea>
            [+editor_custom_buttons1_msg+]
            #2: <textarea name="[+name+]2" id="[+name+]" class="inputBox mce">[+[+editorKey+]_custom_buttons2+]</textarea>
            [+editor_custom_buttons2_msg+]
            #3: <textarea name="[+name+]3" id="[+name+]" class="inputBox mce">[+[+editorKey+]_custom_buttons3+]</textarea>
            #4: <textarea name="[+name+]4" id="[+name+]" class="inputBox mce">[+[+editorKey+]_custom_buttons4+]</textarea>',
        'message'=>'editor_custom_buttons_message',
        'defaultCheckbox'=>true
    ),
    'css_selectors'=>array(
        'title'=>'editor_css_selectors_title',
        'name'=>'[+name+]',
        'configTpl'=>'
            <textarea name="[+name+]" id="[+name+]" class="inputBox mce">[+[+editorKey+]_css_selectors+]</textarea>',
        'message'=>'editor_css_selectors_message',
        'defaultCheckbox'=>true
    )
);
?>