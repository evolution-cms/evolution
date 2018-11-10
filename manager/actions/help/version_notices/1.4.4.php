<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<p><b>Most interesting in this release:</b>:</p>

<ul>
  <li>[I] Redesigned login page, as well as the added opportunity in the settings to specify the logo and background for this page</li>
	<li>[I] Added change of menu position: Top / left (can be changed in settings)</li>
	<li>[F] Fixed problem with scrolling on iOS devices</li>
	<li>[I] Added mobile mode for tinyMCE4</li>
	<li>[F] fixed problem (HTTP2 / SSL & check connection to server)</li>
	<li>[R] Singleton: instead of using <code>global $ modx;</code> it is recommended to use <code>$ modx = EvolutionCMS ();</code></li>
	<li>[F] Fixed bug in the OnParseProperties event</li>
	<li>[F] TVs that are without a category remain in the local tab (when using the settings for moving TV)</li>
	<li>[F] Cross-Site Scripting <a href="https://www.exploit-db.com/exploits/44775/" target="_blank">https://www.exploit-db.com/exploits/44775/</a> Site name field XSS fix</li>
	<li>[I] Added support for Ctr + Alt + L for PhpStorm</li>
	<li>[F] fixed MySql strict mode error in the admin area</li>
	<li>[I] Added events for publication and removal of publication of documents</li>
	<li>[I] Reduced the size of the log that phpmailer produces on error</li>
	<li>[I] Now you can return data from different types of plug-ins, not just lines</li>
	<li>[I] Added OnBeforeMinifyCss event</li>
	<li>[I] Automatic set from in mail from the system settings if not specified</li>
</ul>
