<!DOCTYPE html>
<html>
<head>
    <title>[+site_name_text+] (Evolution CMS Manager Login)</title>
    <meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width">
    <link rel="icon" type="image/ico" href="[+favicon+]">
    <meta name="theme-color" content="[+manager_theme_color+]" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <style>
        html { font-size: 16px }
        body {
            background-color: #0d47a1; /* initial color */
        }
        @media (max-width: 900px) {
            html { font-size: 16px; }
        }
        @media (max-width: 400px) {
            html { font-size: 16px; }
        }
        @supports (-webkit-overflow-scrolling: touch) {
            html, body { -webkit-overflow-scrolling: touch; overflow: auto; height: 100%; /* ios iframe scroll jumping to top fix */ }
        }
        body { font-weight: normal; font-style: normal; font-size: 0.8125rem; line-height: 1.5; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; -webkit-font-smoothing: subpixel-antialiased }
        /* For Arabic Font */
        html[lang="ar"] body, html[lang="ary"] body, html[lang="azb"] body, html[lang="fa-IR"] body, html[lang="haz"] body, html[lang="ps"] body, html[lang="ur"] body { font-family: Tahoma, Arial, sans-serif; }
        /* For Chinese Font */
        html[lang="zh"] body { font-family: "PingFang SC", "Microsoft JhengHei", "Hiragino Sans GB", "Microsoft YaHei", "Source Han Sans SC", Helvetica, sans-serif; }
        /* For Devanagari Font */
        html[lang="bn-BD"] body, html[lang="hi-IN"] body, html[lang="mr-IN"] body { font-family: Arial, sans-serif; }
        /* For Greek Font */
        html[lang="el"] body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        /* For Gujarati Font */
        html[lang="gu-IN"] body { font-family: Arial, sans-serif; }
        /* For Hebrew Font */
        html[lang="he"] body { font-family: "Arial Hebrew", Arial, sans-serif; }
        /* For Japanese Font */
        html[lang="ja"] body { font-family: Meiryo, "Hiragino Kaku Gothic Pro", sans-serif; }
        /* For Korean font */
        html[lang="ko-KR"] body { font-family: "Apple SD Gothic Neo", "Malgun Gothic", "Nanum Gothic", Dotum, sans-serif; }
        /* For Thai Font */
        html[lang="th"] body { font-family: "Sukhumvit Set", "Helvetica Neue", Helvetica, Arial, sans-serif; }
        /* For Arabic Font */
        html[lang="fa"] body { font-family: Tahoma, Arial, sans-serif; }
        body { background-color: #0d47a1; color: #444; }
        body.drag *, body.drag::after { cursor: grabbing !important; cursor: -moz-grabbing !important; cursor: -webkit-grabbing !important; cursor: -moz-grab !important; -moz-user-select: none !important; -webkit-user-select: none !important; user-select: none !important }
        body.drag::after { bottom: 0; content: ""; left: 0; position: fixed; right: 0; top: 0; z-index: 999 }
        body, ul, li { padding: 0; margin: 0; }
        ul { margin-left: 1rem; margin-right: 1rem; }
        li { list-style: none }
        img { max-width: 100%; height: auto }
        a { color: #3481bc; text-decoration: none }
        a:focus, a:hover { color: #014c8c; text-decoration: underline }
        a[href=""], a:not([href]) { cursor: default }
        a:not([href]):not([tabindex]) { color: inherit; text-decoration: none }
        a:not([href]):not([tabindex]):focus, a:not([href]):not([tabindex]):hover { color: inherit; text-decoration: none }
        a:not([href]):not([tabindex]):focus { outline: 0 }
        * { -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box }
        *::-ms-clear { display: none; }
        .text-center { text-align: center !important }
        .text-muted { color: #818a91 !important }
        a.text-muted:focus, a.text-muted:hover { color: #4b5257 !important }

        /* [ FORMS ] */
        #username, #password, #FMP-email { border-radius: 20px!important; }
        #password {margin-bottom: 24px; }
        legend { font-size: 1em; }
        label { cursor: pointer; display: inline-block; margin-bottom: .5em }
        label:not([for]) { cursor: default }
        button, input { margin: 0; padding: 0; border: none; font: inherit; line-height: normal; }
        input[type=checkbox], input[type=radio] { width: 0.8125rem !important; height: 0.8125rem !important; margin-right: 0.25em; vertical-align: -0.15em; border-radius: .1rem;
            border: 1px solid #d4d4d4; background: #fff no-repeat 50% -1em; outline: none; transition: border-color .2s, background-position .1s; }
        input[type=checkbox] { background-image: url("data:image/svg+xml;utf8,%3Csvg%20viewBox%3D%270%200%201792%201792%27%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%3E%3Cpath%20d%3D%27M1671%20566q0%2040-28%2068l-724%20724-136%20136q-28%2028-68%2028t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28%2068-28t68%2028l294%20295%20656-657q28-28%2068-28t68%2028l136%20136q28%2028%2028%2068z%27%20fill%3D%27%23444%27/%3E%3C/svg%3E"); background-size: 1em; }
        input[type=radio] { background-image: url("data:image/svg+xml;utf-8,%3Csvg%20viewBox%3D%270%200%201792%201792%27%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%3E%3Cpath%20d%3D%27M1664%20896q0%20209-103%20385.5t-279.5%20279.5-385.5%20103-385.5-103-279.5-279.5-103-385.5%20103-385.5%20279.5-279.5%20385.5-103%20385.5%20103%20279.5%20279.5%20103%20385.5z%27%20fill%3D%27%23444%27/%3E%3C/svg%3E"); background-size: .545em; border-radius: 50% }
        input[type=checkbox]:hover, input[type=radio]:hover { border-color: #bcbcbc }
        input[type=checkbox]:active, input[type=radio]:active { background-color: #fafafa; }
        input[type=checkbox]:focus, input[type=radio]:focus { border-color: #4d8ef9 !important; box-shadow: 0 0 0 1px rgba(77, 142, 249, 0.5) }
        input[type=checkbox]:checked, input[type=radio]:checked { background-position: 50% 50% }
        input[type=checkbox]:disabled { background-image: url("data:image/svg+xml;utf8,%3Csvg%20viewBox%3D%270%200%201792%201792%27%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%3E%3Cpath%20d%3D%27M1671%20566q0%2040-28%2068l-724%20724-136%20136q-28%2028-68%2028t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28%2068-28t68%2028l294%20295%20656-657q28-28%2068-28t68%2028l136%20136q28%2028%2028%2068z%27%20fill%3D%27%23bbb%27/%3E%3C/svg%3E"); background-size: .875em; }
        button, input, optgroup, select, textarea { position: relative; font-family: inherit; font-size: 0.8125rem; line-height: 1.23076923; margin: 0; -webkit-appearance: none; -moz-appearance: none; }
        .form-control, input[type=text]:not(.form-control), input[type=password], input[type=number], input[type=email], input[type=date], input[type=url], input[type=search], select, textarea { display: inline-block; width: 100%; max-width: 100%; height: auto; padding: 0.46153846em .5em; line-height: 1.23076923; vertical-align: inherit; font-size: 0.8125rem; color: #464a4c; background-color: #fff; -webkit-background-clip: padding-box; background-clip: padding-box; border: 1px solid #d4d4d4; border-radius: .1em; -webkit-transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s; transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s; -o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s; }
        .form-control:focus, input:not([type=radio]):not([type=checkbox]):focus, select:focus, textarea:focus, .btn.focus, .btn:focus { z-index: 3; outline-offset: -1px; outline: rgba(77, 142, 249, 0.5) solid 2px; border-color: #66afe9; }
        input:not([type=button]):not([type=submit]):hover:not(:focus), select:hover:not(:focus), textarea:hover:not(:focus) { border-color: #bbb; transition: border-color 0s, background-position .1s; }
        .btn, button:not(.btn), input[type=button]:not(.btn), input[type=submit]:not(.btn) { display: inline-block; padding: 0.46153846em 1em; line-height: 1.23076923; vertical-align: inherit; text-align: center; text-decoration: none !important; font-size: 0.8125rem; font-weight: 400; white-space: nowrap; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; cursor: pointer; background-color: #fff; border: 1px solid #d4d4d4; border-radius: .1em; -webkit-transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; -o-transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out }
        select:not([size]):not([multiple]), select.form-control:not([size]):not([multiple]), select[size="1"]:not([multiple]) { height: 2.3076923em; line-height: 1.38461538 }
        .btn:hover, input[type=button]:not(.btn):hover, input[type=submit]:not(.btn):hover { color: #292b2c; background-color: #efefef; border-color: #c3c3c3 }
        .btn:focus, input[type=button]:not(.btn):focus, input[type=submit]:not(.btn):focus { background-color: #e6e6e6; border-color: #adadad }
        .btn-success { color: #fff; background-color: #5cb85c; border-color: #56ac56 }
        .btn-success:hover { color: #fff; background-color: #449d44; border-color: #419641 }
        .btn-success:focus, .btn-success:focus:active { color: #fff; background-color: #398439; border-color: #2d672d }
        textarea, textarea.form-control { width: 100%; line-height: 1.5; overflow: auto; resize: vertical }
        select:not([size]):not([multiple]), select[size="1"] { padding-right: 2em !important; background-image: url("data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PScwIDAgMTc5MiAxNzkyJyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnPjxwYXRoIGQ9J00xMzk1IDczNnEwIDEzLTEwIDIzbC00NjYgNDY2cS0xMCAxMC0yMyAxMHQtMjMtMTBsLTQ2Ni00NjZxLTEwLTEwLTEwLTIzdDEwLTIzbDUwLTUwcTEwLTEwIDIzLTEwdDIzIDEwbDM5MyAzOTMgMzkzLTM5M3ExMC0xMCAyMy0xMHQyMyAxMGw1MCA1MHExMCAxMCAxMCAyM3onIGZpbGw9JyUyMzQ0NCcvPjwvc3ZnPg==") !important; background-repeat: no-repeat; background-position: right center; background-size: 2em 1.3em }
        select[size="1"]:not(.unstyled), select[name="which_editor"] { width: auto; }
        optgroup { font-style: normal; font-weight: 500; background-color: #ddd; }
        optgroup option { font-weight: normal; background-color: #fff; }
        input[type=color] { width: 2.308em; height: 2.308em; -webkit-appearance: menulist; -moz-appearance: menupopup }
        input[type=range] { -webkit-appearance: slider-horizontal; -moz-appearance: initial; }
        ::-ms-value { border: none; margin: 0; padding: 0 0 1px; line-height: 1 }
        select::-ms-expand { display: none; }
        ::-moz-focus-inner { border: 0; padding: 0; }
        @-moz-document url-prefix() {
            select:not([size]):not([multiple]) { text-indent: -2px }
        }
        /* darkness */
        .darkness .form-control, .darkness input[type=text]:not(.form-control), .darkness input[type=password], .darkness input[type=number], .darkness input[type=email], .darkness input[type=date], .darkness input[type=url], .darkness input[type=search], .darkness select, .darkness textarea, .darkness .btn, .darkness button:not(.btn), .darkness input[type=button]:not(.btn), .darkness input[type=submit]:not(.btn), .darkness .input-group-addon { border-color: #414449; background-color: #202329; color: #bbb; }
        .darkness input:not([type=button]):not([type=submit]):hover:not(:focus), .darkness select:hover:not(:focus), .darkness textarea:hover:not(:focus) { border-color: #5d5d5d }
        .darkness .btn:hover, .darkness input[type=button]:not(.btn):hover, .darkness input[type=submit]:not(.btn):hover, .darkness .input-group-addon:hover { border-color: #414449; background-color: #282c34; color: #eee; }
        .darkness .btn-success { color: #fff; background-color: #428743; border-color: #479048; }
        .darkness .btn-success.active, .darkness .btn-success:active, .darkness .open > .btn-success.dropdown-toggle { color: #fff; border-color: #419641; background: #448546 none; }
        .darkness .btn-success:hover { color: #fff; background-color: #39773a; border-color: #39773a; }
        .darkness #actions .btn-group .btn-success { box-shadow: none }
        .darkness #actions .dropdown-menu .btn { background-color: #343942; border-radius: 0; color: #fff; }
        .darkness #actions .dropdown-menu .btn:hover { background-color: #3c424e; color: #fff }
        .darkness optgroup { background-color: #202329 }
        .darkness optgroup option { background-color: #1a1c21 }
        .darkness .float-right > select { width: auto }
        .darkness input[type=checkbox], .darkness input[type=radio] { border: 1px solid #414449; background-color: #202329 }
        .darkness input[type=checkbox]:checked, .darkness input[type=radio]:checked { background-color: #ffc107; border-color: #ffc107 !important }
        .darkness input[type=checkbox]:checked:hover, .darkness input[type=radio]:checked:hover { box-shadow: 0 0 .5em #ffc107 }
        .darkness input[type=checkbox]:checked + label { background-color: transparent !important; color: #fff; }
        .darkness input[type=checkbox] + label:hover { color: #fff; }
        .darkness input[type=checkbox]:disabled, .darkness input[type=radio]:disabled, .darkness input[type=checkbox]:checked:disabled, .darkness input[type=radio]:checked:disabled { background-color: #282c34; border-color: #414449 !important; box-shadow: none !important; }
        .darkness select:not([size]):not([multiple]), .darkness select[size="1"] { background-image: url("data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PScwIDAgMTc5MiAxNzkyJyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIGZpbGw9JyNlZWUnPjxwYXRoIGQ9J00xMzk1IDczNnEwIDEzLTEwIDIzbC00NjYgNDY2cS0xMCAxMC0yMyAxMHQtMjMtMTBsLTQ2Ni00NjZxLTEwLTEwLTEwLTIzdDEwLTIzbDUwLTUwcTEwLTEwIDIzLTEwdDIzIDEwbDM5MyAzOTMgMzkzLTM5M3ExMC0xMCAyMy0xMHQyMyAxMGw1MCA1MHExMCAxMCAxMCAyM3onLz48L3N2Zz4=") !important; background-repeat: no-repeat; }
        .darkness input:-webkit-autofill, .darkness textarea:-webkit-autofill, .darkness select:-webkit-autofill, .darkness input:-webkit-autofill:focus { border-color: #ffc107 !important; -webkit-animation-name: autofill; -webkit-animation-fill-mode: both; outline: none }
        .darkness input:-webkit-autofill:focus { box-shadow: 0 0 0.5em #ffc107; }
        @-webkit-keyframes autofill {
            to { color: #bbb; background: transparent; }
        }

        .form-group {
            margin-bottom: 1rem;
        }
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
        body,body.lightness,body.light,body.dark,body.darkness {background: #000 url('[+login_bg+]') no-repeat fixed center !important;background-size: cover !important;margin:0px;
        }
        @media (max-width: 479px) {
            body,body.lightness,body.light,body.dark,body.darkness {background-image: none !important;}
        }

        .page { height: 100%;}
        @media (min-width: 480px) {
            .loginbox-center .page { max-width: 25rem;margin: 10vh auto;height: auto;}
        }
        @media (min-width: 1200px) {
            .loginbox-center .page { margin-top: 20vh; margin-bottom: 20vh;}
        }
        .darkness .page { background-color: transparent;}


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
            background-color: rgba(0, 0, 0, 0.75);
            transition: background ease-in-out .3s;
        }
        .loginbox.loginbox-light {
            background-color: rgba(255, 255, 255, 0.85);
        }
        .loginbox.loginbox-dark {
            background-color: rgba(0, 0, 0, 0.75);
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
            border-radius: 20px;
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
        #mainloader {position: absolute; z-index: 50000; top: 0; left: 0; width: 100%; height: 100%; text-align: center; vertical-align: middle; padding: 15% 0 0 0; background-color: rgba(255, 255, 255, 0.64); opacity: 0; visibility: hidden; -webkit-transition-duration: 0.3s; transition-duration: 0.3s }
        #mainloader.show {opacity: 0.75; visibility: visible; -webkit-transition-duration: 0.1s; transition-duration: 0.1s}
        #mainloader::before {display: none;}
        .evo__logo {font-size: 3.5em;position: fixed; top: 40%; left: 50%;}
        .evo__logo::before {content: ""; display: block; position: absolute; z-index: 1; left: 50%; top: 50%; width: 120px; height: 120px; border-radius: 50%; transform: translate(-50%, -50%) rotate(0deg); animation: rotateLogo 2s linear infinite; box-shadow: 5px 5px 0 0 rgb(234, 132, 82), 14px -7px 0 0 rgba(111, 163, 219, 0.7), -7px 11px 0 0 rgba(112, 193, 92, 0.74), -11px -7px 0 0 rgba(147, 205, 99, 0.78);}
        @keyframes rotateLogo {to {transform: translate(-50%, -50%) rotate(360deg)}}
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
        <form method="post" name="loginfrm" id="loginfrm" class="container container-body" action="?a=0">

            <!-- OnManagerLoginFormPrerender -->
            [+OnManagerLoginFormPrerender+]

            <!-- logo -->
            <div class="form-group form-group--logo text-center">
                <a class="logo" href="../" title="[+site_name_attr+]">
                    <img src="[+login_logo+]" alt="[+site_name_attr+]" id="logo">
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
                var header = this.response.slice(0, 9);
                if (header.toLowerCase() === 'location:') {
                    window.location = this.response.slice(10);
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
