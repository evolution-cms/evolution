<?php
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$triggerRequiredActions = array(19, 23, 300, 77, 101, 108, 106, 107); // when reloadTree = true
$alwaysRefreshActions = array(16, 301, 78, 22, 102, 76); // Always reload tree

$action = isset($_GET['a']) ? (int)$_GET['a'] : 0;
$reload = isset($_SESSION['elementsInTree']['reloadTree']) ? (bool)$_SESSION['elementsInTree']['reloadTree'] : false;

if (($reload === true && in_array($action, $triggerRequiredActions, true)) ||
    in_array($action, $alwaysRefreshActions, true)
) {
    $_SESSION['elementsInTree']['reloadTree'] = false;

    $modx->event->addOutput(
        "<!-- elementsInTree Start -->\n" .
        '<script>jQuery(document).ready(function() { top.reloadElementsInTree();})</script>' .
        "<!-- elementsInTree End -->\n"
    );
}
