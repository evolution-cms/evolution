<!DOCTYPE html>
<html>
<head>
    <title>[(site_name)] (Evolution CMS Manager Login)</title>
    <meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width">
    <link rel="icon" type="image/ico" href="[+favicon+]">
    <link rel="stylesheet" type="text/css" href="media/style/[(manager_theme)]/style.css">
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
            background: #2a313b url('[+login_bg+]') no-repeat fixed center !important;
            background-size: cover !important;
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
                margin: 10vh auto;
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
        .loginbox.loginbox-light {
            background-color: rgba(255, 255, 255, 0.85);
        }
        .loginbox.loginbox-dark {
            background-color: rgba(0, 0, 0, 0.85);
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
        .loginbox.loginbox-light form a {
            color: #666;
        }
        .loginbox.loginbox-light label.text-muted {
            color: #444 !important;
        }
        .loginbox.loginbox-light label#FMP-email_label,
        .loginbox.loginbox-light .form-control:active,
        .loginbox.loginbox-light .captcha input:focus,
        .loginbox.loginbox-light .captcha input:active,
        .loginbox.loginbox-light .form-control:focus,
        .loginbox.loginbox-light input#username,
        .loginbox.loginbox-light input#password,
        .loginbox.loginbox-light input#FMP-email,
        .loginbox.loginbox-light #FMP-email:active,
        .loginbox.loginbox-light #FMP-email:focus {
            color: #555 !important;
        }
        .loginbox.loginbox-light input#username,
        .loginbox.loginbox-light input#password,
        .loginbox.loginbox-light input#FMP-email {
            background-color: rgba(250, 255, 255, 0.4) !important;
            border: 1px solid rgba(113, 116, 117, 0.1) !important;
        }
        .darkness .loginbox form {
            background-color: transparent;
        }
        input[type=checkbox] { width: 0.8125rem !important; height: 0.8125rem !important; margin-right: 0.25em; vertical-align: -0.15em; border-radius: .1rem; border: 1px solid #9ba9bf; background: rgba(250, 255, 255, 0.8) url("data:image/svg+xml;utf8,%3Csvg%20viewBox%3D%270%200%201792%201792%27%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%3E%3Cpath%20d%3D%27M1671%20566q0%2040-28%2068l-724%20724-136%20136q-28%2028-68%2028t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28%2068-28t68%2028l294%20295%20656-657q28-28%2068-28t68%2028l136%20136q28%2028%2028%2068z%27%20fill%3D%27%23444%27/%3E%3C/svg%3E") no-repeat 50% -1em; background-size: contain; outline: none; transition: border-color .2s, background-position .1s; }
        input[type=checkbox]:hover { border-color: #bcbcbc }
        input[type=checkbox]:focus { border-color: #4d8ef9 !important; box-shadow: 0 0 0 1px rgba(77, 142, 249, 0.5) }
        input[type=checkbox]:checked { background-position: 50% 50%; }
        .loginbox.loginbox-dark input[type=checkbox] { border: 1px solid #414449; background-color: #202329 }
        .loginbox.loginbox-dark input[type=checkbox]:hover { border-color: #5d5d5d }
        .loginbox.loginbox-dark input[type=checkbox]:checked { background-color: #FFC107; border-color: #ffc107 !important }
        .loginbox.loginbox-dark input[type=checkbox]:checked:hover { box-shadow: 0 0 0.5em #ffc107 }
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

        .btn,
        #FMP-email_button {
            border-radius: 0;
        }
        .btn-success,
        #FMP-email_button {
            color: #fff !important;
            background-color: #449d44 !important;
            border-color: #419641 !important;
        }
        .btn-success:hover,
        .btn-success:focus,
        #FMP-email_button:hover,
        #FMP-email_button:focus {
            background-color: #5cb85c !important;
            border-color: #5cb85c !important;
        }
        #submitButton,
        #FMP-email_button {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            font-size: 1rem;
            font-weight: 400;
        }
        #submitButton {
            float: right;
        }
        /* onManagerLoginFormRender */

        #onManagerLoginFormRender {
            margin-top: 3rem;
            color: #fff;
        }
        /* FMP - forgot password */

        @media (min-width: 768px) {
            #ForgotManagerPassword-show_form {
                display: inline-block;
                position: absolute;
                z-index: 500;
                bottom: 1.5rem;
            }
        }
        #FMP-email_label {
            color: #818a91
        }
        #FMP-email {
            margin-bottom: 2rem
        }
        #FMP-email_button {
            float: right;
        }
        /* form controls */

        .form-control,
        .captcha input,
        #FMP-email {
            padding: 0.7em 1em !important;
            border-radius: 0 !important;
            transition: all ease-in-out .3s !important;
        }
        .form-control,
        .form-control:active,
        .form-control:focus,
        .captcha input,
        .captcha input:active,
        .captcha input:focus {
            font-size: 1rem !important;
            color: #fff !important;
            background-color: rgba(255, 255, 255, .2) !important;
            border-width: 0 !important;
        }
        .form-control:active,
        .captcha input:focus,
        .captcha input:active,
        .form-control:focus {
            outline: 0 none !important;
            background-color: rgba(255, 255, 255, .3) !important;
        }
        /* form groups */

        .form-group--logo {
            margin-bottom: 1.875rem;
            text-align: left !important;
        }
        .form-group--actions > label {
            padding-top: 0.6875rem;
        }
        /* captcha */

        .captcha {
            margin-bottom: 1rem;
        }
        /* label and caption */

        label,
        .caption {
            color: #818a91;
            line-height: 1.2em;
        }
        .caption {
            margin-bottom: 0.9375rem;
        }
        /* mainloader */

        #mainloader {
            position: absolute;
            z-index: 50000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            text-align: center;
            vertical-align: middle;
            padding: 15% 0 0 0;
            background-color: rgba(255, 255, 255, 0.64);
            opacity: 0;
            visibility: hidden;
            -webkit-transition-duration: 0.3s;
            transition-duration: 0.3s
        }
        #mainloader::before {
            content: "";
            display: block;
            position: absolute;
            z-index: 1;
            left: 50%;
            top: 30%;
            width: 7.5rem;
            height: 7.5rem;
            margin: -3.75rem 0 0 -3.75rem;
            border-radius: 50%;
            animation: rotate 2s linear infinite;
            box-shadow: 0.3125rem 0.3125rem 0 0 rgb(234, 132, 82), 0.875rem -0.4375rem 0 0 rgba(111, 163, 219, 0.7), -0.4375rem 0.6875rem 0 0 rgba(112, 193, 92, 0.74), -0.6875rem -0.4375rem 0 0 rgba(147, 205, 99, 0.78);
        }
        #mainloader.show {
            opacity: 0.75;
            visibility: visible;
            -webkit-transition-duration: 0.1s;
            transition-duration: 0.1s
        }
        /* loader keyframes  */

        @keyframes rotate {
            to {
                transform: rotate(360deg)
            }
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
<body class="[+manager_theme_style+] [+login_form_position_class+]">
<div class="page">
    <div class="tab-page loginbox [+login_form_style_class+]">
        <form method="post" name="loginfrm" id="loginfrm" class="container container-body" action="processors/login.processor.php">

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
                <label for="username" class="text-muted">[+username+]</label>
                <input type="text" class="form-control" name="username" id="username" tabindex="1" value="[+uid+]">
            </div>

            <!-- password -->
            <div class="form-group">
                <label for="password" class="text-muted">[+password+]</label>
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
                    <input type="checkbox" id="rememberme" name="rememberme" value="1" class="checkbox" [+remember_me+]> [+remember_username+]</label>
                <button type="submit" name="submitButton" class="btn btn-success" id="submitButton">[+login_button+]</button>
            </div>

            <!-- OnManagerLoginFormRender -->
            [+OnManagerLoginFormRender+]

        </form>
    </div>

    <!-- copyrights -->
    <div class="copyrights">
        <p class="loginLicense"></p>
        <div class="gpl">&copy; 2005-2020 by the <a href="http://evo.im/" target="_blank">EVO</a>. <strong>EVO</strong>&trade; is licensed under the GPL.</div>
    </div>
</div>

<!-- loader -->
<div id="mainloader"></div>

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
    xhr.open('POST', 'processors/login.processor.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
    xhr.onload = function() {
      if (this.readyState === 4) {
        var header = this.response.substr(0, 9);
        if (header.toLowerCase() === 'location:') {
          window.location = this.response.substr(10);
        } else {
          var cimg = document.getElementById('captcha_image');
          if (cimg) cimg.src = 'includes/veriword.php?rand=' + Math.random();
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
