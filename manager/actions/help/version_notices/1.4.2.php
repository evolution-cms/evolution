<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<ul>
  <li>[I] php cli-install.php (dmi3yy)</li>
	<li>[I] run install file (dmi3yy)</li>
	<li>[I] Install Evo from console (<a href="https://monosnap.com/file/Tj21cmlMhZXNJdRXfKBLAvTlJcElkJ" target="_blank">https://monosnap.com/file/Tj21cmlMhZXNJdRXfKBLAvTlJcElkJ</a>) (dmi3yy)</li>
	<li>[F] fix for use html tags in name (dmi3yy)</li>
	<li>[F] Fix "undefined index"-notice (Deesen)</li>
	<li>[C] TinyMCE4 code clean-up (Deesen)</li>
	<li>[F] sendStrictURI (Ruslan)</li>
	<li>[U] modernize default theme (Serg)</li>
	<li>[I] add .tpl for create file from filemanager (dmi3yy)</li>
	<li>[F] correct getTpl (Serg)</li>
	<li>[I] add composer.json (dmi3yy)</li>
	<li>[F] fix lang error (dmi3yy)</li>
	<li>[U] update DocLister and FormLister (dmi3yy)</li>
	<li>[F] fix escapeshellarg disabled for security reason (dmi3yy)</li>
	<li>[U] Update english.inc.php (Mr B)</li>
	<li>[U] Update mainmenu.php (Mr B)</li>
	<li>[F] #559 Zend OPcache API is restricted by "restrict_api" configuration directive (Pathologic)</li>
	<li>[F] #563 Error when upgrading to 'phpmailer sender property' commit(Pathologic)</li>
	<li>[I] phpmailer sender property (Pathologic)</li>
	<li>[F] fix only variables can be passed by reference (Pathologic)</li>
	<li>[I] log only public properties of MODxMailer (Pathologic)</li>
</ul>
