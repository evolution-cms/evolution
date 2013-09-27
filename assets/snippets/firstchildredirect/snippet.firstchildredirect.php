<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
/*
 * @name FirstChildRedirect
 * @author Jason Coward <jason@opengeek.com>
 * @modified-by Ryan Thrash <ryan@vertexworks.com>
 * @license Public Domain
 * @version 1.0
 * 
 * This snippet redirects to the first child document of a folder in which this
 * snippet is included within the content (e.g. [!FirstChildRedirect!]).  This
 * allows MODx folders to emulate the behavior of real folders since MODx
 * usually treats folders as actual documents with their own content.
 * 
 * Modified to make Doc ID a required parameter... now defaults to the current 
 * Page/Folder you call the snippet from.
 * 
 * &docid=`12` 
 * Use the docid parameter to have this snippet redirect to the
 * first child document of the specified document.
 */

$docid = (isset($docid))? $docid: $modx->documentIdentifier;

$children= $modx->getActiveChildren($docid, 'menuindex', 'ASC');
if (!$children === false) {
    $firstChild= $children[0];
    $firstChildUrl= $modx->makeUrl($firstChild['id']);
} else {
    $firstChildUrl= $modx->makeUrl($modx->config['site_start']);
}
return $modx->sendRedirect($firstChildUrl,0,'REDIRECT_HEADER','HTTP/1.1 301 Moved Permanently');
?>