<?php
/**
 * DLcrumbs snippet
 *
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 */
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}
$_out = '';

if ( isset( $modx->event->params['config'] ) ) {
    require_once MODX_BASE_PATH . 'assets/lib/Helpers/Config.php';

    $helper = new \Helpers\Config( $modx->event->params );
    $helper->setPath( '/assets/snippets/DocLister/' );
    $helper->loadConfig( $modx->event->params['config'] );
    
    $modx->event->params = array_merge( $helper->getConfig(), $modx->event->params );
    extract( $modx->event->params );
}

$_parents = array();
$hideMain = (!isset($hideMain) || (int)$hideMain == 0);
if ($hideMain) {
    $_parents[] = $modx->config['site_start'];
}
$id = isset($id) ? $id : $modx->documentObject['id'];
$tmp = $modx->getParentIds($id);
$_parents = array_merge($_parents, array_reverse(array_values($tmp)));
foreach ($_parents as $i => $num) {
    if ($num == $modx->config['site_start'] && !$hideMain) {
        unset($_parents[$i]);
    }
}

if (isset($showCurrent) && (int)$showCurrent > 0) {
    $_parents[] = $id;
}
if (!empty($_parents) && count($_parents) >= (empty($minDocs) ? 0 : (int)$minDocs)) {
    $_options = array_merge(
        array(
            'config' => 'crumbs:core'
        ),
        !empty($modx->event->params) ? $modx->event->params : array(),
        array(
            'idType'    => 'documents',
            'sortType'  => 'doclist',
            'documents' => implode(",", $_parents)
        )
    );
    $_out = $modx->runSnippet("DocLister", $_options);
}

return $_out;
