<?php
/**
 * TransAlias
 *
 * Human readable URL translation supporting multiple languages and overrides
 *
 * @category    plugin
 * @version     1.0.5
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @package     modx
 * @subpackage  modx.plugins.transalias
 * @internal    @properties &table_name=Trans table;list;common,russian,dutch,german,czech,utf8,utf8lowercase;utf8lowercase &char_restrict=Restrict alias to;list;lowercase alphanumeric,alphanumeric,legal characters;legal characters &remove_periods=Remove Periods;list;Yes,No;No &word_separator=Word Separator;list;dash,underscore,none;dash &override_tv=Override TV name;string;
 * @internal    @events OnStripAlias
 * @internal    @modx_category Manager and Admin
 * @internal    @installset base, sample
 * @author      Olivier B. Deland, additions by Mike Schell, rfoster
 * @author      Many others
 * @lastupdate  31/03/2015
 */
if(!defined('EVO_BASE_PATH')){die('What are you doing? Get out of here!');}
//Initialize parameters
if (!isset ($alias)) { return ; }
if (!isset ($plugin_dir) ) { $plugin_dir = 'transalias'; }
if (!isset ($plugin_path) ) { $plugin_path = EVO_BASE_PATH.'assets/plugins/'.$plugin_dir; }
if (!isset ($table_name)) { $table_name = 'common'; }
if (!isset ($char_restrict)) { $char_restrict = 'lowercase alphanumeric'; }
if (!isset ($remove_periods)) { $remove_periods = 'No'; }
if (!isset ($word_separator)) { $word_separator = 'dash'; }
if (!isset ($override_tv)) { $override_tv = ''; }
if (!class_exists('TransAlias')) {
    require_once $plugin_path.'/transalias.class.php';
}
$trans = new TransAlias($modx);
//see if TV overrides the table name
if(!empty($override_tv)) {
    $tvval = $trans->getTVValue($override_tv);
    if(!empty($tvval)) {
        $table_name = $tvval;
    }
}
//Handle events
$e =& $modx->event;
switch ($e->name ) {
    case 'OnStripAlias':
        $resolved_table_name = $trans->resolveTableName($table_name, $manager_language ?? null);
        if ($trans->loadTable($resolved_table_name, $remove_periods)) {
            $output = $trans->stripAlias($alias,$char_restrict,$word_separator);
            $e->output($output);
            $e->stopPropagation();
        }
        break ;
    default:
        return ;
}
