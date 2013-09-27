<?php
/**
 * @name FileSource
 * @version 0.1
 * 
 * @description Позволяет хранить сниппеты в виде файлов
 * 
 * @author Maxim Mukharev
 * @install
 * Привязываем к следующим событиям:
 * - OnSnipFormRender
 * - OnBeforeSnipFormSave
 * - OnSnipFormPrerender
 */

$output = '';

/**
 * Подготовка информации перед рендером формы редактирования сниппета
 */
if(strpos($modx->event->name,'Plugin')===false)
{
    $elm_name = 'snippets';
    $vals     = 'snippet';
    $include  = 'return require';
    $count    = 47;
}
else
{
    $elm_name = 'plugins';
    $vals     = 'plugincode';
    $include  = 'require';
    $count    = 39;
}

if($modx->event->name==='OnBeforePluginFormSave' || $modx->event->name==='OnBeforeSnipFormSave')
{
    if(isset($_POST['filebinding']) && !empty($_POST['filebinding']))
    {
        $filebinding = trim($modx->db->escape($_POST['filebinding']));
        if(strpos($filebinding,'\\')) $filebinding = str_replace('\\','/',$filebinding);
        if(strpos($filebinding,'../')!==false || substr($filebinding,0,1)==='/')
            $has_filebinding = '0';
        elseif(!empty($filebinding))
        {
            $has_filebinding = '1';
            $elm_path = "assets/{$elm_name}/{$filebinding}";
            $insert_code = $modx->db->escape("{$include} MODX_BASE_PATH.'{$elm_path}';");
        }
        else $has_filebinding = '0';
    }
    else $has_filebinding = '0';
    
    if(isset($_POST['post']) && !empty($_POST['post']))
    {
        if(strpos($_POST['post'],"\r")!==false)
            $code = str_replace(array("\r\n","\r"), "\n", $_POST['post']);
        else $code = $_POST['post'];
    }
    else $code = '';
}

switch ($modx->event->name)
{
    case 'OnPluginFormPrerender':
    case 'OnSnipFormPrerender':
        global $content;
        if(substr(trim($content[$vals]),0,$count) == $include.' MODX_BASE_PATH.\'assets/'.$elm_name.'/')
        {
            $content['file_binding'] = str_replace(array(';','\''),'',trim(substr(trim($content[$vals]),$count,250)));
            $elm_path = "assets/{$elm_name}/" . $content['file_binding'];
            $content[$vals] = file_get_contents(MODX_BASE_PATH . $elm_path);
            // strip out PHP tags (from save_snippet.processor.php)
            if ( strncmp($content[$vals], '<?', 2) == 0 )
            {
                $content[$vals] = substr($content[$vals], 2);
                if ( strncmp( $content[$vals], 'php', 3 ) == 0 ) $content[$vals] = substr($content[$vals], 3);
                if ( substr($content[$vals], -2, 2) === '?>' ) $content[$vals] = substr($content[$vals], 0, -2);
            }
            elseif($vals==='snippet') $content['file_binding'] = '';
            
            $_SESSION['itemname']=$content['name'];
        }
        elseif (substr(trim($content[$vals]),0,7) === '//@FILE') // Added by Carw
        {
            $content['file_binding'] = str_replace(';','',trim(substr(trim($content[$vals]),7,250)));
            $elm_path = "assets/{$elm_name}/" . $content['file_binding'];
            $content[$vals] = file_get_contents(MODX_BASE_PATH . $elm_path);
            // strip out PHP tags (from save_snippet.processor.php)
            if ( strncmp($content[$vals], '<?', 2) == 0 )
            {
                $content[$vals] = substr($content[$vals], 2);
                if ( strncmp( $content[$vals], 'php', 3 ) == 0 ) $content[$vals] = substr($content[$vals], 3);
                if ( substr($content[$vals], -2, 2) === '?>' ) $content[$vals] = substr($content[$vals], 0, -2);
            }
            else $content['file_binding'] = '';
            $_SESSION['itemname']=$content['name'];
        }
        else $_SESSION['itemname']="New snippet";
        break;
    case 'OnSnipFormRender':
    case 'OnPluginFormRender':
        global $content;
        
        $output = '
<script type="text/javascript">
mE1   = new Element("tr");
mE11  = new Element("td",{"align":"left","styles":{"padding-top":"14px"}});
mE12  = new Element("td",{"align":"left","styles":{"padding-top":"14px"}});
mE122 = new Element("input",{"name":"filebinding","type":"text","maxlength":"45","value":"'.$content['file_binding'].'","class":"inputBox","styles":{"width":"300px","margin-left":"14px"},"events":{"change":function(){documentDirty=true;}}});

mE11.appendText("' . _lang('Static file path') . ':");
mE11.inject(mE1);
mE122.inject(mE12);
mE12.inject(mE1);

setPlace = $("displayparamrow");

mE1.inject(setPlace,"after");
</script>
';
        break;
    case 'OnBeforeSnipFormSave':
        if($has_filebinding==='1')
        {
            file_put_contents(MODX_BASE_PATH.$elm_path, $code);
            $GLOBALS['snippet'] = $insert_code;
        }
        break;
    case 'OnBeforePluginFormSave':
        if($has_filebinding==='1')
        {
            file_put_contents(MODX_BASE_PATH.$elm_path, "<?php\n{$code}");
            $GLOBALS['plugincode'] = $insert_code;
        }
        break;
}

if($output != '') $modx->event->output($output);



if(!function_exists('_lang') )
{
    function _lang($msgid)
    {
        global $modx;
        $manager_lang = $modx->config['manager_language'];
        $lang_file_path = dirname(__FILE__) . "/lang/{$manager_lang}.inc.php";
        
        $_lang = array();
        if($manager_lang!=='english' && is_file($lang_file_path))
        {
            include($lang_file_path);
        }
        
        $msgstr = (isset($_lang[$msgid])) ? $_lang[$msgid] : $msgid;
        
        return $msgstr;
    }
}
