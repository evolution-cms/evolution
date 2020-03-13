<?php
/**
 * DocLister snippet
 *
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 */
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}
$_time = microtime(true);
$out = null;
$DLDir = MODX_BASE_PATH . 'assets/snippets/DocLister/';

require_once($DLDir . "core/DocLister.abstract.php");
require_once($DLDir . "core/extDocLister.abstract.php");
require_once($DLDir . "core/filterDocLister.abstract.php");

if (isset($controller)) {
    preg_match('/^(\w+)$/iu', $controller, $controller);
    $controller = $controller[1];
} else {
    $controller = "site_content";
}
$class = $controller;
if (!class_exists($class) || !is_subclass_of($class, '\\DocLister', true)) {
    $class .= 'DocLister';
}
if (!class_exists($class)) {
    $dir = isset($dir) ? MODX_BASE_PATH . $dir : $DLDir . "core/controller/";
    $path = $dir . $controller . '.php';
    if ($class !== 'DocLister' && file_exists($path)) {
        require_once($path);
    }
}

$DLTemplate = DLTemplate::getInstance($modx);
$_templatePath = $DLTemplate->getTemplatePath();
$_templateExtension = $DLTemplate->getTemplateExtension();
if (class_exists($class) && is_subclass_of($class, '\\DocLister', true)) {
    $DocLister = new $class($modx, $modx->Event->params, $_time);
    if ($DocLister->getCFGDef('returnDLObject')) {
        return $DocLister;
    }
    $data = $DocLister->getDocs();
    if($DocLister->getCFGDef("api", 0)){
        $out = $DocLister->getJSON($data,$DocLister->getCFGDef("api", 0));
    }
    else{
        $out = $DocLister->render();
    }
    if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'manager') {
        $debug = $DocLister->debug->showLog();
    } else {
        $debug = '';
    }

    if ($DocLister->getCFGDef('debug', 0)) {
        if ($DocLister->getCFGDef("api", 0)) {
            $modx->setPlaceholder($DocLister->getCFGDef("sysKey", "dl") . ".debug", $debug);
        } else {
            $out = ($DocLister->getCFGDef('debug') > 0) ? $debug . $out : $out . $debug;
        }
    }

    $saveDLObject = $DocLister->getCFGDef('saveDLObject');
    if ($saveDLObject && is_scalar($saveDLObject)) {
        $modx->setPlaceholder($saveDLObject, $DocLister);
    }
}
$DLTemplate->setTemplatePath($_templatePath)->setTemplateExtension($_templateExtension);

return $out;
