<?php
/**
 * ElementsInTree
 *
 * Get access to all Elements and Modules inside Manager sidebar
 *
 */

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$role = $_SESSION['mgrRole'];

if ((!isset($adminRoleOnly) || $adminRoleOnly === 'yes') && (int)$role !== 1) {
    return;
}

$eitBaseBath = MODX_BASE_PATH . 'assets/plugins/elementsintree/';
include_once $eitBaseBath . 'includes/functions.inc.php';

global $_lang;

if (!isset($_SESSION['elementsInTree'])) {
    $_SESSION['elementsInTree'] = array();
}

switch ($modx->event->name) {
    // Trigger reloading tree for relevant actions
    case 'OnManagerMainFrameHeaderHTMLBlock':
        include_once $eitBaseBath . 'includes/on_manager_main_frame_header_html_block.inc.php';
        break;
    case 'OnManagerTreePrerender': // Main elementsInTree-part
        include_once $eitBaseBath . 'includes/on_manager_tree_prerender.inc.php';
        break;
    case 'OnManagerTreeRender':
        if (hasAnyPermission()) {
            include_once $eitBaseBath . 'includes/on_manager_tree_render.inc.php';
        } else {
            $modx->event->addOutput('</div></div>');
        } // Issue 1340
        break;
    case 'OnTempFormSave':
    case 'OnTVFormSave':
    case 'OnChunkFormSave':
    case 'OnSnipFormSave':
    case 'OnPluginFormSave':
    case 'OnModFormSave':
    case 'OnTempFormDelete':
    case 'OnTVFormDelete':
    case 'OnChunkFormDelete':
    case 'OnSnipFormDelete':
    case 'OnPluginFormDelete':
    case 'OnModFormDelete':
        // Set reloadTree = true for this events
        $_SESSION['elementsInTree']['reloadTree'] = true;
        break;
    default:
        if (isset($_GET['r']) && (int)$_GET['r'] === 2) {
            $_SESSION['elementsInTree']['reloadTree'] = true;
        }
        break;
}

return;
