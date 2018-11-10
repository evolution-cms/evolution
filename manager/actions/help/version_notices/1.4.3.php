<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
?>
<p></p>
<p><b>New 4 color mode for Default Theme</b>:</p>
<ul>
  <li>Lightness: everything is light </li>
	<li>Light: the header is dark </li>
	<li>Dark: header and sidebar are dark </li>
  <li>Darkness: everything is dark</li>
</ul>
<p></p>
<p>You can set this in:</p>
<ul>
  <li>Configuration > Interface & features > Color Scheme</li>
	<li>Users > Manager User > Tab user > Color Scheme</li>
	<li>Sidebar-> TreeMenu -> theme color mode switch button <i class="fa fa-lg fa-adjust"></i></li>
</ul>
<p></p>
<p><b>Other things:</b></p>
<ul>
  <li>[U] update DocLister (dmi3yy)</li>
	<li>[U] update Formlister (dmi3yy)</li>
	<li>[I] more checks in cli mode (Pathologic)</li>
	<li>[F] Missing introtext in Recent Resources (Piotr Matysiak)</li>
	<li>[F] Fix #603 bug for resource tree scrolling (Piotr Matysiak)</li>
	<li>[R] moved JS code to a file manager/media/script/main.js (Serg)</li>
	<li>[F] fix empty template on save tv (Serg)</li>
	<li>[F] #577 Fix TinyMCE for [introtext] (Deesen)</li>
	<li>[F] Fix Extras buttons on 1.4.1 #571 (dmi3yy)</li>
	<li>[I] add user_agent info to manager_log (dmi3yy)</li>
	<li>[F] fix 577 TinyMCE introtext mode not work (dmi3yy)</li>
	<li>[F] fix notice (Serg)</li>
	<li>[F] fix TinyMCE disable after update to 1.4.2 (dmi3yy)</li>
	<li>[F] fix possible wrong path calculation (Pathologic)</li>
	<li>[I] Wrap TinyMCE3 Toolbar (Mr B)</li>
	<li>[F] Prevent long select option text values overflowing container (Mr B)</li>
	<li>[I] add view ability for ini files in manager files (dmi3yy)</li>
	<li>[F] fix demo site (Formlister, param reply-to) (dmi3yy)</li>
</ul>
