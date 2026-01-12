<!DOCTYPE html>
<html>
<head>
	<title>[(site_name)] (Evolution CMS Manager Login)</title>
	<meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]">
	<meta name="robots" content="noindex, nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="icon" type="image/ico" href="[+favicon+]">
	<meta name="theme-color" content="#000000" />
	<!-- Preload background image for faster display -->
	<link rel="preload" href="[+login_bg+]" as="image">
	<!-- Critical CSS inline for instant background -->
	<style>
		html, body { min-height: 100%; margin: 0; }
		body { background-image: url('[+login_bg+]'); }
	</style>
	<!-- Main CSS -->
	<link rel="stylesheet" href="[+manager_theme_url+]css/login.css?v=22">
</head>
<body class="[+manager_theme_style+] loginbox-[(login_form_position)]">
<div class="page">
	<div class="tab-page loginbox">
		<form method="post" name="loginfrm" id="loginfrm" class="container container-body" action="processors/login.processor.php">

			<!-- logo -->
			<div class="form-group form-group--logo text-center">
				<a class="logo" href="../" title="[(site_name)]">
					<img src="[+login_logo+]" alt="[(site_name)]" id="logo">
				</a>
			</div>

			<div class="text-muted">
				<h2>[(site_name)]</h2>

				[%manager_lockout_message%]
			</div>

			<!-- actions -->
			<div class="form-group form-group--actions">
				<input type="button" class="btn btn-default" value="[%home%]" onclick="return gotoHome();" />
				<input type="button" class="btn btn-success" value="[%logout%]" onclick="return doLogout();" />
			</div>

		</form>
	</div>

	<!-- copyrights -->
	<div class="copyrights">
		<p class="loginLicense"></p>
		<div class="gpl">&copy; 2004 - 2026 by the <a href="https://evo.im/" target="_blank">Evolution CMS</a>. <strong>Evolution CMS</strong>&trade; is licensed under the GPL.</div>
	</div>
</div>

<!-- script -->
<script>
    function doLogout()
    {
        top.location = '[+logouturl+]';
    }
    function gotoHome()
    {
        top.location = '[+homeurl+]';
    }
</script>
</body>
</html>
