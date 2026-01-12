<!DOCTYPE html>
<html>
<head>
    <title>[(site_name)] (Evolution CMS Manager Login)</title>
    <meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="icon" type="image/ico" href="[+favicon+]">
    <meta name="theme-color" content="#151722" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <!-- Preload background image for faster display -->
    <link rel="preload" href="[+login_bg+]" as="image">
    <!-- Critical CSS inline for instant background -->
    <style>
        html, body { min-height: 100%; margin: 0; }
        body {
            background-image: url('[+login_bg+]');
        }
    </style>
    <link rel="stylesheet" href="[+manager_theme_url+]css/login.css?v=40">
</head>
<body class="[+manager_theme_style+] [+login_form_position_class+]">
<div class="page">
    <div class="tab-page loginbox [+login_form_style_class+]">
        <form method="post" name="loginfrm" id="loginfrm" class="container container-body" action="?a=0">

            <!-- OnManagerLoginFormPrerender -->
            [+OnManagerLoginFormPrerender+]

            <!-- logo -->
            <div class="form-group form-group--logo text-center">
                <a class="logo" href="../" title="[(site_name)]">
                    <img src="[+login_logo+]" alt="[(site_name)]" id="logo">
                </a>
            </div>

            <!-- username -->
            <div class="form-group">
                <label for="username" class="text-muted">[%username%]</label>
                <input type="text" class="form-control" name="username" id="username" tabindex="1" value="[+uid+]">
            </div>

            <!-- password -->
            <div class="form-group">
                <label for="password" class="text-muted">[%password%]</label>
                <input type="password" class="form-control" name="password" id="password" tabindex="2" value="">
            </div>

            <!-- captcha -->
            <div class="captcha clearfix">
                <div class="caption">[+login_captcha_message+]</div>
                <p>[+captcha_image+]</p>
                [+captcha_input+]
            </div>

            <!-- actions -->
            <div class="form-group form-group--actions">
                <label for="rememberme" class="text-muted">
                    <input type="checkbox" id="rememberme" name="rememberme" value="1" class="checkbox" [+remember_me+]> [%remember_username%]</label>
                <button type="submit" name="submitButton" class="btn btn-success" id="submitButton">[%login_button%]</button>
            </div>

            <!-- OnManagerLoginFormRender -->
            [+OnManagerLoginFormRender+]
            [+repair_password+]
        </form>
    </div>

    <!-- copyrights -->
    <div class="copyrights">
        <p class="loginLicense"></p>
        <div class="gpl">&copy; 2004 - 2026 by the <a href="https://evo.im/" target="_blank">Evolution CMS</a>. <strong>Evolution CMS</strong>&trade; is licensed under the GPL.</div>
    </div>
</div>

<!-- loader -->
<div id="mainloader"><div class="evo__logo">EVO</div></div>

<!-- script -->
<script>
    /* <![CDATA[ */
    if (window.frames.length) {
        window.location = self.document.location;
    }
    var form = document.loginfrm;
    if (form.username.value !== '') {
        form.password.focus();
    } else {
        form.username.focus();
    }
    form.onsubmit = function(e) {
        document.getElementById('mainloader').classList.add('show');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '?a=0', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
        xhr.onload = function() {
            if (this.readyState === 4) {
                var header = this.response.substr(0, 9);
                if (header.toLowerCase() === 'location:') {
                    window.location = this.response.substr(10);
                } else {
                    var cimg = document.getElementById('captcha_image');
                    if (cimg) cimg.src = 'captcha.php?rand=' + Math.random();
                    document.getElementById('mainloader').classList.remove('show');
                    alert(this.response);
                }
            }
        };
        xhr.send('ajax=1&username=' + encodeURIComponent(form.username.value) + '&password=' + encodeURIComponent(form.password.value) + (form.captcha_code ? '&captcha_code=' + encodeURIComponent(form.captcha_code.value) : '') + '&rememberme=' + form.rememberme.value);
        e.preventDefault();
    };
    /* ]]> */
</script>
</body>
</html>
