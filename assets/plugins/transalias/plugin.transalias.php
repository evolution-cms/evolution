<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
//Initialize parameters
if (!isset ($alias)) { return ; }
if (!isset ($plugin_dir) ) { $plugin_dir = 'transalias'; }
if (!isset ($plugin_path) ) { $plugin_path = $modx->config['base_path'].'assets/plugins/'.$plugin_dir; }
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
        if ($trans->loadTable($table_name, $remove_periods)) {
            $output = $trans->stripAlias($alias,$char_restrict,$word_separator);
            $e->output($output);
            $e->stopPropagation();
        }
        break ;
    default:
        return ;
}
?>