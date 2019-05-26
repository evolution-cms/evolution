<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<p>EVO 1.4.9 includes various fixes and improvements for stability and backward compatibility.</p>
<p>Evolution 1.4 LTS version</p>
<ul class="small">
	<li>support bugfix, compatible with 2.x branch, stability until 02.02.2020</li>
	<li>support for critical security issues until 12.02.2021</li>
</ul>
<p></p>
<p>Fixes and Updates in 1.4.9:</p>
<ul>

    <li>[I] Add support WebP for phpthumb (Ser1ous)</li>
	<li>[I] New event OnLogEvent (Ser1ous)</li>
	<li>[F] set auto_template_logic -> sibling by default (Dmi3yy)</li>
	<li>[U] Update mutate_web_user.dynamic.php (bossloper)</li>
	<li>[U] Update mutate_user.dynamic.php (bossloper)</li>
	<li>[F] display thislogin date for users / managers (bossloper)</li>
	<li>[F] (security): update htaccess (Dmi3yy)</li>
	<li>[F] #1058 Warning in Admin Backend(Dmi3yy)</li>
	<li>[F] fix modx.js (Serg)</li>
	<li>[F] Undefined index enable_filter (Kamil)</li>
	<li>[U] #1054 Update config.inc.tpl (Dreamer0x01)</li>
	<li>[F] fix missing quality setting for png files (Pathologic)</li>
	<li>[F] #1026 Manager Manager tabs and maps (Dreamer0x01)</li>
	<li>[F] Manager Manager fix google and yandex map (dzhuryn)</li>
	<li>[F] (dbapi) unpacking the host string in a circle (AgelxNash)</li>
	<li>[F] #976 fix wrong language codes (Pathologic)</li>
	<li>[F] (core) fix undefined variable (Serg)</li>

</ul>

<p><b> WebP for phpthumb:</b></p>
<p>For work need:<br>
    - install rosell-dk/webp-convert from composer <br>
    - use not cacheble with param &webp=1
</p>

