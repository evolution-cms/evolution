<?php
/*
*************************************************************************
	EVO Content Management System and PHP Application Framework ("EVO")
	Managed and maintained by Dmytro Lukianenko and the	EVO community
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
	along with EVO (located in "/assets/docs/"); if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1335, USA

	For more information on EVO please visit https://evo.im/
	Github: https://github.com/evolution-cms/evolution/

**************************************************************************
	Based on MODX Evolution CMS and Application Framework
	Copyright 2005 and forever thereafter by Raymond Irving & Ryan Thrash.
	All rights reserved.

	MODX Evolution is originally based on Etomite by Alex Butter
**************************************************************************
*/

/**
 * Initialize Document Parsing
 * -----------------------------
 */
if (! isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}
$mstart = memory_get_usage();

$config = [
    'core' => __DIR__ . '/core',
    'manager' => __DIR__ . '/manager',
    'root' => __DIR__
];

if (file_exists(__DIR__ . '/config.php')) {
    $config = array_merge($config, require __DIR__ . '/config.php');
}
if (! file_exists($config['core'] . '/bootstrap.php')) {
    $path = __DIR__ . '/install/src/template/not_installed.tpl';
    if (file_exists($path)) {
        readfile($path);
    }
    exit;
}
require $config['core'] . '/bootstrap.php';

// set some settings, and address some IE issues
@ini_set('url_rewriter.tags', '');
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_only_cookies',1);
header('P3P: CP="NOI NID ADMa OUR IND UNI COM NAV"'); // header for weird cookie stuff. Blame IE.
header('Cache-Control: private, must-revalidate');
ob_start();

/**
 *	Filename: index.php
 *	Function: This file loads and executes the parser. *
 */

define('IN_PARSER_MODE', true);
if (! defined('IN_MANAGER_MODE')) {
	define('IN_MANAGER_MODE', false);
}
if (! defined('MODX_API_MODE')) {
    define('MODX_API_MODE', false);
}
if (! defined('MODX_CLI')) {
    define('MODX_CLI', false);
}

// initiate a new document parser
$modx = evolutionCMS();

// set some parser options
$modx->minParserPasses = 1; // min number of parser recursive loops or passes
$modx->maxParserPasses = 10; // max number of parser recursive loops or passes
$modx->dumpSQL = false;
$modx->dumpSnippets = false; // feed the parser the execution start time
$modx->dumpPlugins = false;
$modx->mstart = $mstart;

// Debugging mode:
$modx->stopOnNotice = false;

// Don't show PHP errors to the public
if(!isset($_SESSION['mgrValidated']) || !$_SESSION['mgrValidated']) {
    @ini_set("display_errors","0");
}

if (is_cli()) {
    @set_time_limit(0);
    @ini_set('max_execution_time',0);
}

// execute the parser if index.php was not included
if (!MODX_API_MODE && !MODX_CLI) {
    $modx->executeParser();
}
