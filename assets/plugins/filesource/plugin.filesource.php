<?php
/**
 * FileSource
 *
 * Save snippets and plugins to static files
 *
 * @category    plugin
 * @version     0.1
 * @internal    @properties
 * @internal    @events OnSnipFormRender,OnBeforeSnipFormSave,OnSnipFormPrerender,OnPluginFormPrerender,OnPluginFormRender,OnBeforePluginFormSave
 * @internal    @modx_category Manager and Admin
 * @internal    @installset base
 * @reportissues https://github.com/modxcms/evolution
 * @author      Maxim Mukharev
 * @author      By Carw, and Bumkaka
 * @lastupdate  06/05/2016
 */
if(!defined('MODX_BASE_PATH')) die('What are you doing? Get out of here!');

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
            $elm_path = "assets/{$elm_name}/{$filebinding}";
            $pInfo = pathinfo(MODX_BASE_PATH.$elm_path);
            if(is_dir($pInfo['dirname'])) {
                $has_filebinding = '1';
                $insert_code = $modx->db->escape("{$include} MODX_BASE_PATH.'{$elm_path}';");
            };
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
        global $content, $_lang;
        if(substr(trim($content[$vals]),0,$count) == $include.' MODX_BASE_PATH.\'assets/'.$elm_name.'/')
        {
            $content['file_binding'] = str_replace(array(';','\''),'',trim(substr(trim($content[$vals]),$count,250)));
            $elm_path = "assets/{$elm_name}/" . $content['file_binding'];
            $content[$vals] = is_readable(MODX_BASE_PATH . $elm_path) ? file_get_contents(MODX_BASE_PATH . $elm_path) : 'File not found: '.$elm_path;
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
            $content[$vals] = is_readable(MODX_BASE_PATH . $elm_path) ? file_get_contents(MODX_BASE_PATH . $elm_path) : 'File not found: '.$elm_path;
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
        if (preg_match('/\s' . $_lang['duplicated_el_suffix'] . '\s?\d*$/', $content['name'])) {
            $content['file_binding'] = '';
        }
        //else $_SESSION['itemname']="New snippet";
        break;
    case 'OnSnipFormRender':
    case 'OnPluginFormRender':
        global $content;
        $output = '
        <script>  
        if (el = document.querySelector(\'#displayparamrow\') || false) {
          el.innerHTML +=
              \'<table style="width: 100%; margin-top: 1rem;">\' +
              \' <tr>\' +
              \'  <th style="width: 10rem">' . _lang('Static file path') . '</th>\' +
              \'  <td>\' +
              \'    <input type="text" class="inputBox" name="filebinding" value="' . $content['file_binding'] . '" maxlength="75" onchange="documentDirty=true;">\' +
              \'  </td>\' +
              \' </tr>\' +
              \'</table>\';
        }
        </script>';
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
            $phpTag = substr($code,0,5) == '<?php' ? '' : "<?php\n";
            file_put_contents(MODX_BASE_PATH.$elm_path, "{$phpTag}{$code}");
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
