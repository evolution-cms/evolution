<?php
/**
 * @name        CodeMirror
 * @description JavaScript library that can be used to create a relatively pleasant editor interface
 *
 * @released    Jun 5, 2013
 * @CodeMirror  3.13
 *
 * @required    MODx 0.9.6.3+
 *              CodeMirror  3.13 : pl
 *
 * @confirmed   MODx Evolution 1.10.0
 *
 * @author      Mihanik71 
 *
 * @see         https://github.com/Mihanik71/CodeMirror-MODx
 */

$textarea_name 			= 'content';
$mode 					= 'htmlmixed';
$lang 					= 'htmlmixed';
$theme                  = 'default';
$indentUnit             = 4;
$tabSize                = 4;
$lineWrapping           = true;
$matchBrackets          = true;
$activeLine           	= true;
$emmet					= '<script src="'.$_CM_URL.'cm/emmet-compressed.js"></script>';
$search					= '<script src="'.$_CM_URL.'cm/search-compressed.js"></script>';

$array_path 	= explode(".", $_REQUEST['path']);
$length 		= count($array_path);
$language 		= strtolower($array_path[($length-1)]);

/*
 * Switch lang
 */
	switch($language){
		case "css" :
			$mode = "text/css";
			$lang = "css";
		break;
		case "js" :
			$mode = "text/javascript";
			$lang = "javascript";
		break;
		case "json" :
			$mode = "application/json";
			$lang = "javascript";
		break;
		case 'php' :
			$mode  = 'application/x-httpd-php-open';
			$lang = "php";
		break;
		case 'sql' :
			$mode  = 'text/x-mysql';
			$lang = "sql";
		break;
	}
    $output = <<< HEREDOC
	<link rel="stylesheet" href="{$_CM_URL}cm/lib/codemirror.css">
	<link rel="stylesheet" href="{$_CM_URL}cm/theme/{$theme}.css">
	<script src="{$_CM_URL}cm/lib/codemirror-compressed.js"></script>
	<script src="{$_CM_URL}cm/addon-compressed.js"></script>
	<script src="{$_CM_URL}cm/mode/{$lang}-compressed.js"></script>
	{$emmet}{$search}
	<script type="text/javascript">
		//Basic settings
		var config = {
			mode: '{$mode}',
			theme: '{$theme}',
			indentUnit: {$indentUnit},
			tabSize: {$tabSize},
			lineNumbers: true,
			matchBrackets: {$matchBrackets},
			lineWrapping: {$lineWrapping},
			gutters: ["CodeMirror-linenumbers", "breakpoints"],
			styleActiveLine: {$activeLine},
			indentWithTabs: true,
			extraKeys:{
				"Ctrl-Space": function(cm){
					var n = cm.getCursor().line;
					var info = cm.lineInfo(n);
					foldFunc(cm, n);
					cm.setGutterMarker(n, "breakpoints", info.gutterMarkers ? null : makeMarker("+"));
				},
				"F11": function(cm) {
					setFullScreen(cm, !isFullScreen(cm));
				},
				"Esc": function(cm) {
					if (isFullScreen(cm)) setFullScreen(cm, false);
				}
			}
		};
		var foldFunc = CodeMirror.newFoldFunction(CodeMirror.tagRangeFinder);
		var myTextArea = document.getElementsByName('{$textarea_name}')[0];
		var myCodeMirror = (CodeMirror.fromTextArea(myTextArea, config));
		myCodeMirror.hasFocus();
		myCodeMirror.on("gutterClick", function(cm, n) {
			var info = cm.lineInfo(n);
			foldFunc(cm, n);
			cm.setGutterMarker(n, "breakpoints", info.gutterMarkers ? null : makeMarker("+"));
		});
    </script>
HEREDOC;
echo $output;
