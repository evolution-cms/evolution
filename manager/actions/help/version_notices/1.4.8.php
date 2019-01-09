<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<p>Evolution 1.4 LTS version</p>
<ul>
	<li>support bugfix, compatible with 2.x branch, stability until 02.02.2020</li>
	<li>support for critical security issues until 12.02.2021</li>
</ul>	
<p>EVO 1.4.8 includes the update of several importants things, as well as various fixes and improvements for stability and backward compatibility.</p>
<p></p>
<p>Fixes and Updates in 1.4.8:</p>
<ul>
	<li>[refactor] (Extras) Updater - update only minor version(1.4 to 1.5,  2.0 to 2.1)  (Update across major version  (1.4 to 2.0 or 2.6 to 3.0 ) need do manual or from another plugin) (Dmi3yy)</li>
	<li>[fix] (phpthumb): skipping svg files (Serg)</li>
	<li>[fix] only if  Enable modifiers in settings: #1200 Enable modifiers in Wayfinder - add nested placeholders to $tags like for $fetch = "phx:input=`[+wf.linktext+]`:test". (Dmi3yy)</li>
	<li>[fix] (core) buttons on welcome page, not work with Permission (Dmi3yy)</li>
	<li>fix] Lightness navbar logo at width < 1200px (Nicola)</li>
	<li>[fix] ElementsInTree should be sorted by name #887 (Dmi3yy)</li>
	<li>[fix] ser Icon Standardisation Suggestion #349 (Dmi3yy)</li>
	<li>[refactor] clear (core) welcome.static  @IF (Dmi3yy)</li>
	<li>[fix] (managermanager): path to the jquery file (Agel_Nash)</li>
	<li>[feat] (manager) show template id in select on Document edit page (Dmi3yy)</li>
	<li>[feat] (core): more compatible with 2.x branch (Agel_Nash)</li>
	<li>[fix] (ElementsInTree) Categories are not sorted by name ASC (Nicola)</li>
</ul>