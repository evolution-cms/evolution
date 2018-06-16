<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    exit();
}

if(!$modx->hasPermission('delete_plugin')) {
	$e->setError(3);
	$e->dumpError();
}

$tbl_site_plugins = $modx->getDatabase()->getFullTableName('site_plugins');
$tbl_site_plugin_events = $modx->getDatabase()->getFullTableName('site_plugin_events');

// Get unique list of latest added plugins by highest sql-id
$rs = $modx->getDatabase()->query("SELECT t1.id FROM {$tbl_site_plugins} t1 LEFT JOIN {$tbl_site_plugins} t2 ON (t1.name = t2.name AND t1.id < t2.id) WHERE t2.id IS NULL;");
$latestIds = array();
while($row = $modx->getDatabase()->getRow($rs)) {
    $latestIds[] = $row['id'];
}

// Get list of plugins with disabled and enabled versions
$rs = $modx->getDatabase()->query("SELECT id FROM {$tbl_site_plugins} t1 WHERE disabled = 1 AND name IN (SELECT name FROM {$tbl_site_plugins} t2 WHERE t1.name = t2.name AND t1.id != t2.id)");

while($row = $modx->getDatabase()->getRow($rs)) {

    $id = $row['id'];

    if(in_array($id,$latestIds)) continue;	// Keep latest version of disabled plugins

    // invoke OnBeforePluginFormDelete event
    $modx->invokeEvent('OnBeforePluginFormDelete', array('id'=> $id));

    // delete the plugin.
    if (!$modx->getDatabase()->delete($tbl_site_plugins, "id={$id}")) {
        echo "Something went wrong while trying to delete plugin {$id}";
        exit;
    } else {
        // delete the plugin events.
        if (!$modx->getDatabase()->delete($tbl_site_plugin_events, "pluginid={$id}")) {
            echo "Something went wrong while trying to delete the plugin events for plugin {$id}";
            exit;
        } else {
            // invoke OnPluginFormDelete event
            $modx->invokeEvent('OnPluginFormDelete', array('id'=>$id));
        }
    }
}

// empty cache
$sync = new EvolutionCMS\Cache();
$sync->setCachepath("../assets/cache/");
$sync->setReport(false);
$sync->emptyCache(); // first empty the cache

header('Location: index.php?a=76');
