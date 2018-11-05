<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<p>The main works in 1.4.6 were aimed at stability of work + on safety, now the <b>OutdatedExtrasCheck plugin</b> takes information from the server, thus information on additions that have security problems will appear on the dashboard, which will give even more chances to learn about possible problems with security and fix it promptly</p>
<p></p>
<p>From interesting in 1.4.6:</p>
<ul>
  <li>Support for working with MySQL 8.0</li>
	<li>Support for MySQL in strict mode</li>
	<li>Support for working with PHP 7.3.0RC3</li>
	<li>OutdatedExtrasCheck now checks outdated add-ons from the server and not locally.</li>
	<li>A big DocLister update to version 2.4.0 (read more here: <a href="https://github.com/AgelxNash/DocLister/releases/tag/2.4.0" target="_blank">https://github.com/AgelxNash/DocLister/releases/tag/2.4.0</a>)</li>
	<li>update FormLister to 1.8.0</li>
	<li>phpMailer has been updated to 6.0.5</li>
	<li>Finally removed mootools.js</li>
	<li>Correct transfer of the event name when using nested events #844</li>
	<li>Styled webAlertAndQuit #26</li>
	<li>Check for a minimum version of AjaxSearch updated to version 1.12.1 (I strongly recommend updating AjaxSearch for security and virus protection purposes)</li>
	<li>Added the ability to specify the login form in the light version for those who do not like the dark :)</li>
	<li>Fixed a lot of errors, a full list of which can be found here: <a href="https://github.com/evolution-cms/evolution/blob/1.4.x/assets/docs/changelog.txt" target="_blank">https://github.com/evolution-cms/evolution/blob/1.4.x/assets/docs/changelog.txt</a></li>
	<li>Fixed bug with displaying SVG</li>
	<li>Fixed URL generation for document created via MODxAPI</li>
	<li>rewrote the methods: getChunk and parseChunk on those that DLTemplate</li>
  <li>Fixed a bug in the getTemplateVar, getTemplateVars API with the choice of fields</li>
  <li>Corrected: Managers do not show user groups</li>
  <li>Multiple XSS vulnerabilities in admin panel closed</li>
</ul>
