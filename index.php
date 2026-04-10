<?php
/*
*************************************************************************
	Evolution CMS Content Management System and PHP Application Framework ("EVO")
	Managed and maintained by Dmytro Lukianenko, Serhii Korneliuk and the EVO community
*************************************************************************
	EVO is an opensource PHP/MySQL content management system and content
	management framework that is flexible, adaptable, supports XHTML/CSS
	layouts, and works with most web browsers.

	EVO is distributed under the GNU General Public License
*************************************************************************

	This file and all related or dependant files distributed with this file
	are considered as a whole to make up EVO.

	EVO is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	EVO is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with EVO (located in "/assets/docs/license.txt"); if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1335, USA

	For more information on EVO please visit https://evo.im/
	Github: https://github.com/evolution-cms/evolution/

**************************************************************************
	Based on MODX Evolution CMS and Application Framework
	Copyright 2004 and forever thereafter by Raymond Irving & Ryan Thrash.
	All rights reserved.

	MODX Evolution is originally based on Etomite by Alex Butter
**************************************************************************
*/

/**
 * Front controller for Evolution CMS.
 *
 * Bootstraps the core (`core/bootstrap.php`), defines request-mode constants, and dispatches the request.
 */
$_SERVER['REQUEST_TIME_FLOAT'] ??= microtime(true);
$mstart = memory_get_usage();

$config = [
    'core' => __DIR__ . '/core',
    'root' => __DIR__
];

$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $config = array_replace($config, require $configPath);
}
unset($configPath);

$installMarkerPath = $config['core'] . '/.install';
if (!defined('IN_INSTALL_MODE') && !is_file($installMarkerPath)) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');

    $path = __DIR__ . '/install/src/template/not_installed.tpl';
    if (is_file($path)) {
        readfile($path);
    } else {
        echo '<h3>Unable to load configuration settings</h3>';
        echo 'Please run the Evolution CMS install utility';
    }

    exit;
}

if (!defined('IN_INSTALL_MODE')) {
    define('IN_INSTALL_MODE', false);
}
if (IN_INSTALL_MODE) {
    // Set some settings, and address some IE issues.
    @ini_set('url_rewriter.tags', '');
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_only_cookies', 1);
    }
}

require $config['core'] . '/bootstrap.php';

if (IN_INSTALL_MODE === false) {
    header('P3P: CP="NOI NID ADMa OUR IND UNI COM NAV"'); // header for weird cookie stuff. Blame IE.
    header('Cache-Control: private, must-revalidate');
}
ob_start();

/**
 *    Filename: index.php
 *    Function: This file loads and executes the parser. *
 */

define('IN_PARSER_MODE', true);
if (!defined('IN_MANAGER_MODE')) {
    define('IN_MANAGER_MODE', false);
}
/**
 * Disables automatic request dispatching in the front controller.
 *
 * When enabled, this file will not call `$evo->processRoutes()`.
 */
if (!defined('EVO_API_MODE')) {
    define('EVO_API_MODE', defined('MODX_API_MODE') ? (bool)MODX_API_MODE : false);
}
if (!defined('MODX_API_MODE')) {
    define('MODX_API_MODE', EVO_API_MODE);
}
if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', $config['core'] . '/');
}
if (!defined('EVO_CLI')) {
    define('EVO_CLI', false);
}

/**
 * @deprecated
 * @since 3.5.3
 *
 * Use $evo or/and evo() instead.
 *
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 */
$GLOBALS['modx'] = $modx = evo();
// Initiate a new document parser
$GLOBALS['evo'] = $evo = evo();
$evo->minParserPasses = 1; // min number of parser recursive loops or passes
$evo->maxParserPasses = 10; // max number of parser recursive loops or passes
$evo->dumpSQL = false;
$evo->dumpSnippets = false; // feed the parser the execution start time
$evo->dumpPlugins = false;
$evo->mstart = $mstart;

if (defined('EVO_SESSION') && EVO_SESSION) {
    \EvoSessionProxy::init();
}

// Debugging mode:
$evo->stopOnNotice = false;

// Don't show PHP errors to the public
if (!isset($_SESSION['mgrValidated']) || !$_SESSION['mgrValidated']) {
    @ini_set('display_errors', '0');
}

if (is_cli()) {
    @set_time_limit(0);
    @ini_set('max_execution_time', 0);
}

// Execute the parser if index.php was not included
if (!EVO_API_MODE && !EVO_CLI && (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE === false)) {
    $evo->processRoutes();
}

unset($evo, $installMarkerPath);
