<!DOCTYPE html>
<html>
<head>
	<title>[+site_name_text+] (Evolution CMS Manager Login)</title>
	<meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]">
	<meta name="robots" content="noindex, nofollow">
	<meta name="viewport" content="width=device-width">
	<link rel="icon" type="image/ico" href="[+favicon+]">
	<link rel="stylesheet" type="text/css" href="[+manager_theme_url+]style.css">
	<style>
		html {
			font-size: 16px;
		}
		html,
		body {
			min-height: 100%;
			height: 100%;
		}
		body.loginbox-center {
			min-height: 1px;
			height: auto;
		}
		body,
		body.lightness,
		body.light,
		body.dark,
		body.darkness {
			background-color: #2a313b !important;
			background-image: url('[+login_bg+]') !important;
			background-size: cover !important;
			background-position: center !important;
			background-repeat: no-repeat !important;
			background-attachment: fixed !important;
		}
		@media (max-width: 479px) {
			body,
			body.lightness,
			body.light,
			body.dark,
			body.darkness {
				background-image: none !important;
			}
		}
		/* page div */
		.page {
			height: 100%;
		}
		@media (min-width: 480px) {
			.loginbox-center .page {
				max-width: 25rem;
				margin-top: 10vh;
				margin-bottom: 10vh;
				margin-left: auto;
				margin-right: auto;
				height: auto;
			}
		}
		@media (min-width: 1200px) {
			.loginbox-center .page {
				margin-top: 20vh;
				margin-bottom: 20vh;
			}
		}
		.darkness .page {
			background-color: transparent;
		}
		/* loginbox */
		.loginbox {
			width: 100%;
			min-height: 100vh;
			box-shadow: none;
			will-change: transform;
			transform: translate3d(0, 0, 0);
			-webkit-animation-name: anim-loginbox;
			-webkit-animation-duration: .5s;
			-webkit-animation-iteration-count: 1;
			-webkit-animation-timing-function: ease;
			-webkit-animation-fill-mode: forwards;
			animation-name: anim-loginbox;
			animation-duration: .5s;
			animation-iteration-count: 1;
			animation-timing-function: ease;
			animation-fill-mode: forwards;
		}
		.loginbox-right .loginbox {
			-webkit-animation-name: anim-loginbox-right;
			animation-name: anim-loginbox-right;
		}
		.loginbox-center .loginbox {
			-webkit-animation-name: anim-loginbox-center;
			animation-name: anim-loginbox-center;
		}
		@media (min-width: 480px) {
			.loginbox {
				max-width: 25rem;
				box-shadow: 0 0 0.5rem 0 rgba(0, 0, 0, .5);
			}
			.loginbox-right .loginbox {
				margin-left: auto;
			}
			.loginbox-center .loginbox {
				min-height: 1px;
			}
		}
		.loginbox,
		.dark .loginbox,
		.darkness .loginbox {
			background-color: rgba(0, 0, 0, 0.85);
			transition: background ease-in-out .3s;
		}
		@media (max-width: 479px) {
			.loginbox,
			.dark .loginbox,
			.darkness .loginbox {
				background-color: transparent;
			}
		}
		/* form */
		.loginbox form a {
			color: #818a91;
		}
		.darkness .loginbox form {
			background-color: transparent;
		}
		/* container */
		.container-body {
			padding: 1.75rem;
		}
		@media (min-width: 480px) {
			.container-body {
				padding: 2.5rem;
			}
		}
		.darkness > .container-body {
			background-color: transparent;
		}
		/* copyrights */
		.copyrights {
			width: 100%;
			padding: .5rem 1.5rem 1.5rem 1.75rem;
			font-size: .675rem;
			color: #aaa;
			text-align: left;
			background-color: rgba(0, 0, 0, 0.15);
		}
		@media (min-width: 480px) {
			.copyrights {
				max-width: 25rem;
				padding-left: 2.5rem;
				background-color: rgba(0, 0, 0, 0.85);
			}
			.loginbox-right .copyrights {
				margin-left: auto;
			}
		}
		@media (min-width: 480px) and (max-width: 767px) {
			.loginbox-center .copyrights {
				will-change: transform;
				transform: translate3d(0, 0, 0);
				-webkit-animation-name: anim-loginbox-center;
				-webkit-animation-duration: .5s;
				-webkit-animation-iteration-count: 1;
				-webkit-animation-timing-function: ease;
				-webkit-animation-fill-mode: forwards;
				animation-name: anim-loginbox-center;
				animation-duration: .5s;
				animation-iteration-count: 1;
				animation-timing-function: ease;
				animation-fill-mode: forwards;
			}
		}
		@media (min-width: 768px) {
			.copyrights {
				position: fixed;
				right: 0;
				bottom: 0;
				width: auto;
				max-width: none;
				text-align: right;
				background-color: transparent;
			}
			.loginbox-right .copyrights {
				left: 0;
				right: auto;
				padding-left: 1.5rem;
			}
			.loginbox-center .copyrights {
				right: auto;
				left: 50%;
				transform: translate3d(-50%, 0, 0);
			}
		}
		.copyrights a {
			color: #fff
		}
		/* buttons */
		.btn {
			border-radius: 0;
		}
		.btn-success {
			color: #fff !important;
			background-color: #449d44 !important;
			border-color: #419641 !important;
		}
		.btn-success:hover,
		.btn-success:focus {
			background-color: #5cb85c !important;
			border-color: #5cb85c !important;
		}
		/* loginbox keyframes */
		@-webkit-keyframes anim-loginbox {
			from {
				opacity: 0;
				transform: translate3d(-10%, 0, 0);
			}
			to {
				opacity: 1;
				transform: translate3d(0, 0, 0);
			}
		}
		@keyframes anim-loginbox {
			from {
				opacity: 0;
				transform: translate3d(-10%, 0, 0);
			}
			to {
				opacity: 1;
				transform: translate3d(0, 0, 0);
			}
		}
		@-webkit-keyframes anim-loginbox-right {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		@keyframes anim-loginbox-right {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		@-webkit-keyframes anim-loginbox-center {
			from {
				opacity: 0;
				transform: translate3d(0, 1.5rem, 0);
			}
			to {
				opacity: 1;
				transform: translate3d(0, 0, 0);
			}
		}
		@keyframes anim-loginbox-center {
			from {
				opacity: 0;
				transform: translate3d(0, 1.5rem, 0);
			}
			to {
				opacity: 1;
				transform: translate3d(0, 0, 0);
			}
		}
	</style>
</head>
<body class="[+manager_theme_style+] loginbox-[(login_form_position)]">
<div class="page">
	<div class="tab-page loginbox">
		<form method="post" name="loginfrm" id="loginfrm" class="container container-body" action="processors/login.processor.php">

			<!-- logo -->
			<div class="form-group form-group--logo text-center">
				<a class="logo" href="../" title="[+site_name_attr+]">
					<img src="[+login_logo+]" alt="[+site_name_attr+]" id="logo">
				</a>
			</div>

			<div class="text-muted">
				<h2>[+site_name_text+]</h2>

				[%manager_lockout_message%]
			</div>

			<!-- actions -->
			<div class="form-group form-group--actions">
				<input type="button" class="btn btn-default" value="[%site%]" onclick="return gotoHome();" />
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
