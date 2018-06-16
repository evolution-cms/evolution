//<?php
/**
 * ManagerManager
 *
 * Customize the EVO Manager to offer bespoke admin functions for end users or manipulate the display of document fields in the manager.
 *
 * @category plugin
 * @version 0.6.3
 * @license http://creativecommons.org/licenses/GPL/2.0/ GNU Public License (GPL v2)
 * @internal @properties &remove_deprecated_tv_types_pref=Remove deprecated TV types;list;yes,no;yes &config_chunk=Configuration Chunk;text;mm_rules
 * @internal @events OnDocFormRender,OnDocFormPrerender,OnBeforeDocFormSave,OnDocFormSave,OnDocDuplicate,OnPluginFormRender,OnTVFormRender
 * @internal @modx_category Manager and Admin
 * @internal @installset base
 * @internal @legacy_names Image TV Preview, Show Image TVs
 * @reportissues https://github.com/DivanDesign/MODXEvo.plugin.ManagerManager/
 * @documentation README [+site_url+]assets/plugins/managermanager/readme.html
 * @documentation Official docs http://code.divandesign.biz/modx/managermanager
 * @link        Latest version http://code.divandesign.biz/modx/managermanager
 * @link        Additional tools http://code.divandesign.biz/modx
 * @link        Full changelog http://code.divandesign.biz/modx/managermanager/changelog
 * @author      Inspired by: HideEditor plugin by Timon Reinhard and Gildas; HideManagerFields by Brett @ The Man Can!
 * @author      DivanDesign studio http://www.DivanDesign.biz
 * @author      Nick Crossland http://www.rckt.co.uk
 * @author      Many others
 * @lastupdate  22/01/2018
 */

// Run the main code
include($modx->config['base_path'].'assets/plugins/managermanager/mm.inc.php');
