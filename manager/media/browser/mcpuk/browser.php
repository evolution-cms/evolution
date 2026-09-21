<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html" />
	<script type="text/javascript">
		window.opener.KCFinder = {
			callBack: function(url) {
				window.opener.KCFinder = null;
				window.opener.SetUrl(url);
			}
		};
<?php
	if(isset($_GET['type']))     $type = htmlspecialchars(trim($_GET['type']), ENT_QUOTES);
	elseif(isset($_GET['Type'])) $type = htmlspecialchars(trim($_GET['Type']), ENT_QUOTES);
    else                         $type = 'images';
    
    if($type==='image') $type = 'images';
    
	$opener = (isset($_GET['editor'])) ? 'opener=' . htmlspecialchars(trim($_GET['editor']), ENT_QUOTES) : '';
	$request_uri = "{$opener}&type={$type}";
?>
		window.location.href = "browse.php?<?php echo $request_uri; ?>";
	</script>
</head>

<body>
</body>
</html>
