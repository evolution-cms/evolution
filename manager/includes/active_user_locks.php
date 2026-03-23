<?php
/**
 * active_user_locks.php
 *
 * This page is requested by several actions to keep elements/resources locked
 */
define('IN_MANAGER_MODE', true);
define('EVO_API_MODE', true);
include_once('../../index.php');

EvolutionCMS()->invokeEvent('OnManagerPageInit');
$ok = false;

if (EvolutionCMS()->elementIsLocked($_GET['type'], $_GET['id'], true)) {
    EvolutionCMS()->lockElement($_GET['type'], $_GET['id']);
    $ok = true;
}

header('Content-type: application/json');
echo $ok ? '{status:"ok"}' : '{status:"null"}';
