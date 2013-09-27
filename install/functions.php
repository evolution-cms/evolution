<?php
function install_sessionCheck()
{
	global $_lang;
	
	// session loop-back tester
	if(!isset($_GET['action']) || $_GET['action']!=='mode')
	{
		if(!isset($_SESSION['test']) || $_SESSION['test']!=1)
		{
			echo '
<html>
<head>
	<title>Install Problem</title>
	<style type="text/css">
		*{margin:0;padding:0}
		body{margin:150px;background:#eee;}
		.install{padding:10px;border:3px solid #ffc565;background:#ffddb4;margin:0 auto;text-align:center;}
		p{ margin:20px 0; }
		a{margin-top:30px;padding:5px;}
	</style>
</head>
<body>
	<div class="install">
		<p>' . $_lang["session_problem"] . '</p>
		<p><a href="./">' .$_lang["session_problem_try_again"] . '</a></p>
	</div>
</body>
</html>';
		exit;
		}
	}
}
