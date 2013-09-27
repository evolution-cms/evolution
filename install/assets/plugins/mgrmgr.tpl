//<?php
/**
 * ManagerManager
 *
 * Customize the MODx Manager to offer bespoke admin functions for end users.
 *
 * @category plugin
 * @version 	0.3.11
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties &config_chunk=Configuration Chunk;text;mm_rules; &remove_deprecated_tv_types_pref=Remove deprecated TV types;list;yes,no;yes &which_jquery=jQuery source;list;local (assets/js),remote (google code),manual url (specify below);local (assets/js) &js_src_type=jQuery URL override;text;
 * @internal	@events OnDocFormRender,OnDocFormPrerender,OnTVFormRender
 * @internal @modx_category Manager and Admin
 * @internal    @installset base
 * @internal @legacy_names Image TV Preview, Show Image TVs
 */


$js_default_url_local = $modx->config['site_url']. '/assets/js/jquery.min.js';
$js_default_url_remote = 'http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js';
$asset_path = $modx->config['base_path'] . 'assets/plugins/managermanager/mm.inc.php';
include($asset_path);
