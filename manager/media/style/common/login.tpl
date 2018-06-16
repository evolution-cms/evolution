<!DOCTYPE html>
<html>
<head>
    <title>[(site_name)] (Evolution CMS Manager Login)</title>
    <meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="viewport" content="width=device-width">
    <style type="text/css">
        html { font-size: 16px; height: 100% }
        body { margin: 0; padding: 0 1rem; height: 100%; font-family: sans-serif; font-size: 0.8rem; line-height: 1.5; color: #666; background-color: #F2F2F2; }
        html, body, #mx_loginbox { height: 100%; }
        * { -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }
        a { color: #999; text-decoration: underline; -webkit-transition-duration: 0.15s; transition-duration: 0.15s; }
        a:hover { color: #444; text-decoration: none }
        p { margin: 0; }
        img { display: inline-block; max-width: 100% }
        .text-center { text-align: center }
        .clear { clear: both; width: 100%; }
        #mx_loginbox { height: auto; min-height: 100%; width: 19rem; margin: 0 auto; padding-top: 7%; }
        #mx_loginbox::before, #mx_loginbox::after { content: ""; display: table; width: 100% }
        .logo { display: inline-block; padding-bottom: 1rem }
        fieldset { display: block; margin: 0 0 2rem; padding: 1.5rem; background-color: #fff; border: none; border-radius: 0.15rem; box-shadow: 0 0 0.5rem rgba(0, 0, 0, 0.1); }
        label { display: block; font-size: 0.8rem; margin: 0 0 0.375rem; }
        input.text, input[type=text] { font-family: inherit; border: 1px solid rgba(0, 0, 0, 0.1); background-color: #fff; margin: 0 0 1rem; padding: 0 0.5rem; width: 100%; height: 2.2rem; line-height: 2.2rem; font-size: 1.2rem; border-radius: 0.15rem; box-shadow: 0 0 5px rgba(188, 188, 188, 0.15); outline: none; -webkit-transition-duration: 0.15s; transition-duration: 0.15s; }
        input.text:focus, input[type=text]:focus { border-color: #5b9bda }
        input.checkbox { margin: 0.5rem 0 0 0.25rem; display: inline-block; width: 1rem; height: 1rem; vertical-align: text-bottom }
        label.remtext, .caption { display: inline-block; font-size: 0.75rem; color: #999 }
        #submitButton, #FMP-email_button { display: block; width: 100%; padding: 0 1rem; margin: 0; min-width: 6rem; height: 2.2rem; line-height: 2.2rem; outline: none; font-size: 0.8rem; color: #fff; cursor: pointer; background-color: #32AB9A; border: none; border-radius: 0.15rem; box-shadow: 0 0 5px rgba(188, 188, 188, 0.15); -webkit-transition-duration: 0.15s; transition-duration: 0.15s; }
        #submitButton:hover, #FMP-email_button:hover { background-color: #35baa8; }
        #submitButton:active, #FMP-email_button:active { background-color: #32AB9A; }
        #FMP-email_button { margin-top: 0 }
        .loginCaptcha { display: block; padding: 0.7rem 0 0.5rem; }
        .caption { display: block }
        .form-footer { }
        #onManagerLoginFormRender > a:first-child, #onManagerLoginFormRender > label:first-child { display: inline-block; padding-top: 1rem; }
        .loginCaptcha img { height: 60px }
        .gpl { float: right; color: #B2B2B2; margin: -2em 0.5em 0.5em; font-size: 0.85em; }
        .gpl a, .loginLicense a { color: #B2B2B2; }
        /* animate */
        .animated { -webkit-animation-duration: 1s; animation-duration: 1s; -webkit-animation-fill-mode: both; animation-fill-mode: both; }
        .animated.infinite { -webkit-animation-iteration-count: infinite; animation-iteration-count: infinite; }
        .animated.hinge { -webkit-animation-duration: 2s; animation-duration: 2s; }
        .scaleX { -webkit-animation: scaleX 3s ease-in-out infinite; animation: scaleX 3s ease-in-out infinite; }
        @keyframes scaleX {
            50% { -webkit-transform: scaleX(0.9); transform: scaleX(0.9) }
            100% { -webkit-transform: scaleX(1); transform: scaleX(1) }
            }
        .zoomIn { -webkit-animation-name: zoomIn; animation-name: zoomIn; -webkit-animation-duration: 1s; animation-duration: 1s; -webkit-animation-fill-mode: both; animation-fill-mode: both; }
        @-webkit-keyframes zoomIn {
            0% { opacity: 0; -webkit-transform: scale3d(.3, .3, .3); transform: scale3d(.3, .3, .3); }
            50% { opacity: 1; }
            }
        @keyframes zoomIn {
            0% { opacity: 0; -webkit-transform: scale3d(.3, .3, .3); transform: scale3d(.3, .3, .3); }
            50% { opacity: 1; }
            }
    </style>
</head>
<body>
<div id="mx_loginbox">
    <form method="post" name="loginfrm" id="loginfrm" action="processors/login.processor.php">
        [+OnManagerLoginFormPrerender+]
        <fieldset>
            <div class="text-center">
                <a class="logo" href="../" title="[(site_name)]">
                    <img src="media/style/[(manager_theme)]/images/misc/login-logo.png" alt="[(site_name)]" id="logo" />
                </a>
            </div>
            <label for="username">[+username+]</label>
            <input type="text" class="text" name="username" id="username" tabindex="1" value="[+uid+]" />
            <label for="password">[+password+]</label>
            <input type="password" class="text" name="password" id="password" tabindex="2" value="" />
            <p class="caption">[+login_captcha_message+]</p>
            <p>[+captcha_image+]</p>
            [+captcha_input+]
            <div class="clear"></div>
            <div class="form-footer">
                <button type="submit" name="submitButton" class="login" id="submitButton">[+login_button+]</button>
            </div>
            [+OnManagerLoginFormRender+]
        </fieldset>
    </form>
</div>
<p class="loginLicense"></p>
<div class="gpl">&copy; 2005-2018 by the <a href="http://evo.im/" target="_blank">EVO</a>. <strong>EVO</strong>&trade; is licensed under the GPL.</div>
</body>
<script type="text/javascript">
    /* <![CDATA[ */
    if(window.frames.length) {
        window.location = self.document.location;
    }
    var form = document.loginfrm;
    if(form.username.value !== '') {
        form.password.focus()
    } else {
        form.username.focus()
    }
    form.onsubmit = function(e) {
        form.submitButton.classList.add('scaleX');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'processors/login.processor.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
        xhr.onload = function() {
            if(this.readyState === 4) {
                var header = this.response.substr(0, 9);
                if(header.toLowerCase() === 'location:') {
                    window.location = this.response.substr(10);
                } else {
                    var cimg = document.getElementById('captcha_image');
                    if(cimg) cimg.src = 'captcha.php?rand=' + Math.random();
                    form.submitButton.classList.remove('scaleX');
                    alert(this.response);
                }
            }
        };
        xhr.send('ajax=1&username=' + encodeURIComponent(form.username.value) + '&password=' + encodeURIComponent(form.password.value) + (form.captcha_code ? '&captcha_code=' + encodeURIComponent(form.captcha_code.value) : ''));
        e.preventDefault();
    }
    /* ]]> */
</script>
</html>
