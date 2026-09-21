<?php

/** This file is part of KCFinder project
  *
  *      @desc Browser calling script
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

require "core/autoload.php"; // Init MODX

function returnNoPermissionsMessage($role) {
	global $_lang;
	echo sprintf($_lang['files_management_no_permission'], $role);
	exit;
}

if( $_GET['type'] == 'images' && !EvolutionCMS()->hasPermission('file_manager') && !EvolutionCMS()->hasPermission('assets_images')) returnNoPermissionsMessage('assets_images');
if( $_GET['type'] == 'files'  && !EvolutionCMS()->hasPermission('file_manager') && !EvolutionCMS()->hasPermission('assets_files'))  returnNoPermissionsMessage('assets_files');

// Only the page itself and the thumbnails it embeds are fetched without a token; every other
// act, including reads, is scripted through browser.baseGetData() and carries the session one.
$act = isset($_GET['act']) ? $_GET['act'] : 'browser';
if (!in_array($act, ['browser', 'thumb'], true)) {
    $token = isset($_REQUEST['_token']) && is_string($_REQUEST['_token']) ? $_REQUEST['_token'] : '';
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        die(json_encode(['error' => 'Invalid CSRF token.']));
    }
}

$browser = new browser($modx);
$browser->action();
