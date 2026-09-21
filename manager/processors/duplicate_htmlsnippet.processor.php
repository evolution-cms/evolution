<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('new_chunk')) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// count duplicates
$htmlsnippet = EvolutionCMS\Models\SiteHtmlsnippet::findOrFail($id);
$name = $htmlsnippet->name;
$count = EvolutionCMS\Models\SiteHtmlsnippet::where('name', 'like', $name.' '.$_lang['duplicated_el_suffix'].'%')->count();
if($count>=1) $count = ' '.($count+1);
else $count = '';

// duplicate htmlsnippet
$newHtmlsnippet = $htmlsnippet->replicate();
$newHtmlsnippet->name = $htmlsnippet->name.' '.$_lang['duplicated_el_suffix'].$count;

// The copy gets the code, not the file. The original's file is named after the
// original, so the copy carries the resolved contents in its row and writes its
// own file the first time it is saved.
$store = \EvolutionCMS\Support\ChunkFileStore::make();
$newHtmlsnippet->snippet = $store->resolve((string) $htmlsnippet->name, $htmlsnippet->snippet);

$newHtmlsnippet->push();

$_SESSION['itemname'] = $newHtmlsnippet->name;

// finish duplicating - redirect to new chunk
$header="Location: index.php?r=2&a=78&id=".$newHtmlsnippet->getKey();
header($header);
