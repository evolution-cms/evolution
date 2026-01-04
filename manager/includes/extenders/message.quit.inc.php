<?php
/**
 * Message Quit Template
 *
 */
if(( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) && IN_PARSER_MODE!="true") {
    die("<b>INCLUDE ACCESS ERROR</b><br /><br />Direct access to this file prohibited.");
}

$parsedMessageString = "
<html><head><title>Evolution CMS Content Manager $version &raquo; $release_date</title>
<style>TD, BODY { font-size: 11px; font-family:verdana; }</style>
<script type='text/javascript'>
	function copyToClip()
	{
		holdtext.innerText = sqlHolder.innerText;
		Copied = holdtext.createTextRange();
		Copied.execCommand('Copy');
	}
</script>
</head><body>
";
if($is_error) {
	$parsedMessageString .= "<h3 style='color:red;background:#e0e0e0;padding:2px;'>&nbsp;MODX Parse Error </h3>
	<table border='0' cellpadding='1' cellspacing='0'>
	<tr><td colspan='3'>MODX encountered the following error while attempting to parse the requested resource:</td></tr>
	<tr><td colspan='3'><b style='color:red;'>&laquo; $msg &raquo;</b></td></tr>";
} else {
	$parsedMessageString .= "<h3 style='color:#003399; background:#eeeeee;padding:2px;'>&nbsp;MODX Debug/ stop message </h3>
	<table border='0' cellpadding='1' cellspacing='0'>
	<tr><td colspan='3'>The MODX parser recieved the following debug/ stop message:</td></tr>
	<tr><td colspan='3'><b style='color:#003399;'>&laquo; $msg &raquo;</b></td></tr>";
}

if(!empty($query)) {
	$parsedMessageString .= "<tr><td colspan='3'><hr size='1' width='98%' style='color:#e0e0e0'/><b style='color:#999;font-size: 9px;border-left:1px solid #c0c0c0; margin-left:10px;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;SQL:&nbsp;<span id='sqlHolder'>$query</span></b><hr size='1' width='98%' style='color:#e0e0e0'/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:copyToClip();' style='color:#821517;font-size: 9px; text-decoration: none'>[Copy SQL to ClipBoard]</a><textarea id='holdtext' style='display:none;'></textarea></td></tr>";
}

if($text!='') {

	$errortype = [ 
		E_ERROR          => "Error",
		E_WARNING        => "Warning",
		E_PARSE          => "Parsing Error",
		E_NOTICE          => "Notice",
		E_CORE_ERROR      => "Core Error",
		E_CORE_WARNING    => "Core Warning",
		E_COMPILE_ERROR  => "Compile Error",
		E_COMPILE_WARNING => "Compile Warning",
		E_USER_ERROR      => "User Error",
		E_USER_WARNING    => "User Warning",
		E_USER_NOTICE    => "User Notice",
	];

	$parsedMessageString .= "<tr><td>&nbsp;</td></tr><tr><td colspan='3'><b>PHP error debug</b></td></tr>";

	$parsedMessageString .= "<tr><td valign='top'>&nbsp;&nbsp;Error: </td>";
	$parsedMessageString .= "<td colspan='2'>$text</td><td>&nbsp;</td>";
	$parsedMessageString .= "</tr>";

	$parsedMessageString .= "<tr><td valign='top'>&nbsp;&nbsp;Error type/ Nr.: </td>";
	$parsedMessageString .= "<td colspan='2'>".$errortype[$nr]." - $nr</b></td><td>&nbsp;</td>";
	$parsedMessageString .= "</tr>";

	$parsedMessageString .= "<tr><td>&nbsp;&nbsp;File: </td>";
	$parsedMessageString .= "<td colspan='2'>$file</td><td>&nbsp;</td>";
	$parsedMessageString .= "</tr>";

	$parsedMessageString .= "<tr><td>&nbsp;&nbsp;Line: </td>";
	$parsedMessageString .= "<td colspan='2'>$line</td><td>&nbsp;</td>";
	$parsedMessageString .= "</tr>";
	if($source!='') {
		$parsedMessageString .= "<tr><td valign='top'>&nbsp;&nbsp;Line $line source: </td>";
		$parsedMessageString .= "<td colspan='2'>$source</td><td>&nbsp;</td>";
		$parsedMessageString .= "</tr>";
	}
}

$parsedMessageString .= "<tr><td>&nbsp;</td></tr><tr><td colspan='3'><b>Parser timing</b></td></tr>";

$parsedMessageString .= "<tr><td>&nbsp;&nbsp;MySQL: </td>";
$parsedMessageString .= "<td><i>[[^qt]] s</i></td><td>(<i>[[^q]] Requests</i>)</td>";
$parsedMessageString .= "</tr>";

$parsedMessageString .= "<tr><td>&nbsp;&nbsp;PHP: </td>";
$parsedMessageString .= "<td><i>[[^p]] s</i></td><td>&nbsp;</td>";
$parsedMessageString .= "</tr>";

$parsedMessageString .= "<tr><td>&nbsp;&nbsp;Total: </td>";
$parsedMessageString .= "<td><i>[[^t]] s</i></td><td>&nbsp;</td>";
$parsedMessageString .= "</tr>";

$parsedMessageString .= "</table>";
$parsedMessageString .= "</body></html>";
