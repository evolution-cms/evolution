<?php
// this is the old error handler. Here for legacy, until i replace all the old errors.
class errorHandler{

var $errorcode;
var $errors = array();

	function errorHandler() {
		
		$_lang = $this->include_lang('errormsg');
		
		$this->errors = array(
		0	=>	$_lang["No errors occured."],
		1	=>	$_lang["An error occured!"],
		2	=>	$_lang["Document's ID not passed in request!"],
		3	=>	$_lang["You don't have enough privileges for this action!"],
		4	=>	$_lang["ID passed in request is NaN!"],
		5	=>	$_lang["The document is locked!"],
		6	=>	$_lang["Too many results returned from database!"],
		7	=>	$_lang["Not enough/ no results returned from database!"],
		8	=>	$_lang["Couldn't find parent document's name!"],
		9	=>	$_lang["Logging error!"],
		10	=>	$_lang["Table to optimise not found in request!"],
		11	=>	$_lang["No settings found in request!"],
		12	=>	$_lang["The document must have a title!"],
		13	=>	$_lang["No user selected as recipient of this message!"],
		14	=>	$_lang["No group selected as recipient of this message!"],
		15	=>	$_lang["The document was not found!"],
	
		100 =>	$_lang["Double action (GET & POST) posted!"],
		600 =>	$_lang["Document cannot be it's own parent!"],
		601 =>	$_lang["Document's ID not passed in request!"],
		602 =>	$_lang["New parent not set in request!"],
		900 =>	$_lang["don't know the user!"], // don't know the user!
		901 =>	$_lang["wrong password!"], // wrong password!
		902 =>	$_lang["Due to too many failed logins, you have been blocked!"],
		903 =>	$_lang["You are blocked and cannot log in!"],
		904 =>	$_lang["You are blocked and cannot log in! Please try again later."],
		905 =>	$_lang["The security code you entered didn't validate! Please try to login again!"]
	);
	}

	function include_lang($context='common') {
		global $modx;
		$_lang = array();
		
		$context = trim($context,'/');
		if(strpos($context,'..')!==false) return;
		
		if($context === 'common')
			$lang_path = MODX_MANAGER_PATH . 'includes/lang/';
		else
			$lang_path = MODX_MANAGER_PATH . "includes/lang/{$context}/";
		include_once($lang_path . 'english.inc.php');
		$manager_language = $modx->config['manager_language'];
		if(is_file("{$lang_path}{$manager_language}.inc.php"))
			include_once("{$lang_path}{$manager_language}.inc.php");
		return $_lang;
	}
	
	function setError($errorcode, $custommessage=""){
		$this->errorcode=$errorcode;
		$this->errormessage=$this->errors[$errorcode];
		if($custommessage!="") {
			$this->errormessage=$custommessage;
		}
	}	
	
	function getError() {
		return $this->errorcode;
	}
	
	function dumpError(){
?>
	<html>
	<head>
	<title>MODx :: Error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $modx_manager_charset; ?>">
	<script>
		function showError(){
			alert("<?php echo $this->errormessage; ?>");
			history.back(-1);
		}
		setTimeout("showError()",10);
	</script>
	</head>
	<body>
	</body>
	</html>
<?php
		exit;
	}
}
?>