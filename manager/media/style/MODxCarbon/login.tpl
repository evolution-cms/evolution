<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <title>MODx CMF Manager Login</title>
    <meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]" />
    <meta name="robots" content="noindex, nofollow" />
    <style type="text/css">
    /* Normalize CSS part */
    html,body,div,span,h1,h2,h3,h4,h5,h6,p,em,img,strong,b,u,i,dl,dt,dd,ol,ul,li,fieldset,form,label,table,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,figcaption,figure,footer,header,hgroup,menu,nav,section,summary,time,mark,audio,video{margin:0;padding:0;border:0;outline:0;vertical-align:baseline;background:transparent;font-size:100%;}
    a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent;}
    table{border-collapse:collapse;border-spacing:0;}td,td img{vertical-align:top;}input,select,button,textarea{margin:0;font-size:100%;}
    input[type="text"],input[type="password"],textarea{padding:0;}input[type="checkbox"]{vertical-align:bottom;}
    input[type="radio"]{vertical-align:text-bottom;}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block;}
    html{overflow-y:scroll;}body{line-height:1;background:#fff;color:#111;text-align:left;font:12px Verdana,"Geneva CY","DejaVu Sans",sans-serif;}
    input,select,button,textarea{font-family:Verdana,"Geneva CY","DejaVu Sans",sans-serif;}
    label,input[type="button"],input[type="submit"],button{cursor:pointer;}a,a:visited,a:focus,a:active,a:hover{cursor:pointer;}
    /* Login Styles part*/
    html {
    float: none;
    background: #f7f7f7;
    }
    body {
    display: block;
    position: absolute;
    background: none;
    width: 100%;
    height: 100%;
    margin: auto;
    min-width: 800px;
    min-height: 500px;
    padding: 0;
    font-family: Arial, sans-serif;
    font-weight: normal;
    font-size: 12px;
    color: #555;
    line-height: 20px;
    text-shadow: 0 1px 0 #fff;
    text-align: left;
    overflow: hidden;
    }
    body * {
    line-height: 20px;
    }
    a {
    color: #00577d;
    text-decoration: none;
    }
    a:active, a:hover {
    color: #0f1e76;
    }
    h1, h2, h3, h4, h5 {
    float: none;
    clear: both;
    margin: 5px 0 26px 0;
    line-height: 32px;
    color: #111;
    text-indent: 20px;
    text-shadow: 0 1px 0 #fff;
    font-weight: normal;
    }
    h1 {
    font-size: 30px;
    }
    h2 {
    font-size: 26px;
    line-height: 28px;
    }
    h3 {
    font-size: 24px;
    line-height: 26px;
    }
    h4 {
    font-size: 20px;
    line-height: 22px;
    }
    h5 {
    font-size: 18px;
    line-height: 20px;
    }
    p {
    text-indent: 20px;
    margin-bottom: 20px;
    }
    ul, ol {
    margin: 0 0 20px 40px;
    }
    input, textarea {
    border: 1px solid #ddd;
    border-left-color: #c3c3c3;
    border-top-color: #acacac;
    padding: 2px 5px !important;
    vertical-align: baseline;
    border-radius: 3px;
    background: #fff url("data:image/png;base64,R0lGODlhAQASAKIAAP////39/fz8/Pr6+vn5+ff39/X19ezs7CH5BAQUAP8ALAAAAAABABIAAAMIeGZE8iDKmQAAOw==") repeat-x top left;
    outline: none;
    }
    input:focus, textarea:focus {
    border: 1px solid #73829a;
    box-shadow: none;
    }
    button, .button {
    color: #222c36;
    font-weight: bold;
    font-size: 12px;
    background: #c7ced2 url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAAAsCAYAAACpOaImAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJ9JREFUeNrM0jsOwyAQBFAsJQKBgEOmyg1zGhcuqfjIDhE/c4Gh2CpIVE9T7O5s53l+GHjbGOOF8DGxQuy9F4ittR/EWivGUkqGmHPGyeu6vhC99zjpnMPJ4zjwnPu+YwwhMBrGGImYUiLirAlb7ZatroJx/idEIQROSikxKqUwaq2JaIwhorX2v5A+Cn19y6ss77lsAud82b43wluAAQAooVGvQzXRvQAAAABJRU5ErkJggg==") repeat-x top left;
    padding: 2px 5px !important;
    white-space: nowrap;
    vertical-align: top;
    text-decoration: none;
    border-radius: 4px;
    text-shadow: 0 1px 1px #fff;
    border:1px solid #8ea4be;
    outline: none;
    }
    button:hover, .button:hover {
    background-position: bottom left;
    box-shadow:  0 0 10px #b8c7d6;
    }
    radiogroup {
    float: left;
    clear: both;
    }
    label {
    float: left;
    display: block;
    clear: both;
    width: 100%;
    margin-bottom: 10px;
    }
    label input, label textarea, label radiogroup {
    float: left;
    clear: both;
    }
    #logo {
    float: none;
    display: block;
    width: 200px;
    margin: 30px auto 10px;
    }
    #mx_loginbox {
    position: relative;
    margin: 0 -215px;
    width: 400px;
    top: 30%;
    left: 50%;
    }
    .sectionBody {
    float: left;
    width: 400px;
    height: auto;
    border: 1px solid #e3e3e3;
    border-top: 1px solid #e3e3e3;
    padding: 10px;
    display: block;
    background: #fff url("data:image/png;base64,R0lGODlhCgAeAMQAAPLy8fLy8v39/P/+/v///vv7+v/+//7///z9/fPy8vLz8v38/fP08/z8+/Tz8/z9/PPz8vv7+/Pz8/z8/PX19fn5+fb29vr6+vf39/T09Pj4+P7+/v39/fHx8f///wAAACH5BAAAAAAALAAAAAAKAB4AAAVhYCeOZGmeYgAAgbqqCSRDAaTIjqTvEiNlwKCQQiwaLcikEsNsOjXQqLRCrVov2Ky2EOlGuN7JpCEuizmCh0CwQHMQnLh8vqnb74cB4WAg6A0DHoKDhIWGh4iJiouMjY6FIQA7") repeat-x left top;
    z-index: 99;
    border-radius: 0;
    }
    .loginLicense {
    float: left;
    width: 100%;
    margin: 30px 0 0;
    clear: both;
    color: #bbb;
    font-size: 11px;
    text-align: center;
    text-indent: 0;
    }
    .sectionHeader {
    float: left;
    display: block;
    padding: 10px 1%;
    width: 98%;
    height: 25px;
    line-height: 25px;
    background: #3f5165 url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAABGCAMAAAD7EUTDAAAAA3NCSVQICAjb4U/gAAADAFBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAARGyUSHCcUHSgVHyoWICwXIi0YIy8ZJDEbJjIcJzQdKDUeKjcfKzkhLDoiLjwjLz4kMT8lMkEmM0MoNUQpNkYqN0crOUksOkstPEwvPU4wPlAxQFEyQVMzQ1U1RFg3Rlo4SF06SmA7S2M9TWU+T2hAUWtCU25DVHBFVnNGWHZIWnlKXHtLXX5NX4FOYYNQY4ZSZYlTZoxVaI9WapFYbJRabpdbb5ldcZxec59gdaJhdqVjeKdleqpmfK1ofq9pf7JrgbVsg7huhbtwhr1wh774hzJrAAAAnUlEQVQ4jW3I10IBAABA0fv/v0Qyy86WLSt7i4T8wD2Ph4AgKHgRhASvgrAgIogKYoK4ICF4E7wLkoKUIC3ICLKCnCAv+BAUBEVBSVAWVARVQU1QF3wKGoKmoCVoCzqCrqAn6Au+BAPBUDASjAUTwbdgKpgJ5oKFYClYCdaCjWAr2An2goPgKDgJfgRnwUXwK7gK/gQ3wV3wEPwLzSd3M7t9/4m/oAAAAABJRU5ErkJggg==") repeat-x top left;
    color: #919fae;
    text-shadow: 0 1px 0 #000;
    font-weight: bold;
    font-size: 24px;
    border-bottom: 1px solid #8B96A0;
    }
    .sectionHeader b {
    font-size: 12px;
    font-weight: normal;
    color: #89939E;
    margin: 0 0 0 10px;
    padding: 0 0 0 10px;
    border-left: 1px solid #4a4f54;
    }
    p.loginMessage {
    float: left;
    display: block;
    position: relative;
    margin: 0;
    padding: 5px 10px;
    border-radius: 5px 5px 0 0;
    width: 400px;
    font-weight: bold;
    color: #fff;
    text-align: left;
    text-indent: 0;
    background: #657587;
    text-shadow: 0 1px 0 #000;
    border: 1px solid #7e8b9a;
    border-bottom: 1px solid #657587;
    }
    .username, .password, .rememberme {
    float: left;
    width: 190px;
    margin: 0;
    padding: 0;
    clear: none;
    font-weight: bold;
    }
    .username input, .password input {
    width: 180px;
    }
    .username {
    margin-right: 20px;
    }
    .rememberme {
    margin: 17px 0 0;
    }
    #rememberme {
    margin: 4px 7px 0 0;
    }
    #submitButton {
    float: right;
    margin: 10px 0 0;
    }
    #ForgotManagerPassword-show_form {
    float: left;
    clear: both;
    width: 100%;
    }
    #onManagerLoginFormRender {
    float: left;
    clear: both;
    color: #aaa;
    width: 400px;
    margin: 10px -10px -10px;
    background: #ebebeb url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAfCAIAAABRS8vCAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYxIDY0LjE0MDk0OSwgMjAxMC8xMi8wNy0xMDo1NzowMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNS4xIFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MzBERjhFN0JBQjg0MTFFMjgyQTA4ODFBMEI3RDkyNkUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MzBERjhFN0NBQjg0MTFFMjgyQTA4ODFBMEI3RDkyNkUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDozMERGOEU3OUFCODQxMUUyODJBMDg4MUEwQjdEOTI2RSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDozMERGOEU3QUFCODQxMUUyODJBMDg4MUEwQjdEOTI2RSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pp4T6SYAAABOSURBVHjaYvz58ycDbsDy69cvPNJMDHgBVPr///9wEg6AXBa4EFYVxBlOG2kWNLfQU/dAunzYemwgXU7Iach5gpGREc2lKC7H9AVAgAEA8t81/rS0y1UAAAAASUVORK5CYII=") repeat-x top left;
    padding: 5px 10px 15px;
    border-top: 1px solid #e3e3e3;
    }
    #FMP-email_button {
    float: right;
    }
    .error {
    color: #a10000;
    }
    .captcha {
    float: left;
    width: 100%;
    margin: 0;
    padding: 0;
    }
    p.caption {
    margin: 15px 0 15px;
    font-size: 12px;
    line-height: 16px;
    color: #aaa;
    text-indent: 0;
    }
    #captcha_image {
    float: left;
    margin: 0 30px 0 10px;
    padding: 0;
    border: 5px solid #fff;
    clear: left;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .captcha label {
    float: left;
    clear: right;
    margin: 0 0 5px;
    width: 50%;
    font-weight: bold;
    }
    </style>
    <script src="media/script/mootools/mootools.js" type="text/javascript"></script>
    <script type="text/javascript">
    /* <![CDATA[ */
        if (top.frames.length!=0) {
            top.location=self.document.location;
        }

        window.addEvent('domready', function() {
            $('submitButton').addEvent('click', function(e) {
                 e = new Event(e).stop();
                 params = 'ajax=1&' + $('loginfrm').toQueryString();
                 url = 'processors/login.processor.php';
                 new Ajax(url,
                    {
                        method: 'post',
                        postBody: params,
                        onComplete:ajaxReturn
                    }
                ).request();
                $$('input').setProperty('readonly', 'readonly');
            });

            // Initial focus
            if ($('username').value != '') {
                $('password').focus();
            } else {
                $('username').focus();
            }
        });

        function ajaxReturn(response) {
            var header = response.substr(0,9);
            if (header.toLowerCase()=='location:') top.location = response.substr(10);
            else {
                var cimg = $('captcha_image');
                if (cimg) {
                    cimg.src = 'includes/veriword.php?rand=' + Math.random();
                }
                $$('input').removeProperty('readonly');
                alert(response);
            }
        }
    /* ]]> */
    </script>
</head>
<body id="login">
<div class="sectionHeader">[+site_name+]<b>MODX Evolution</b></div>
<div id="mx_loginbox">
    <form method="post" name="loginfrm" id="loginfrm" action="processors/login.processor.php">
    <!-- anything to output before the login box via a plugin? -->
    [+OnManagerLoginFormPrerender+]
        <p class="loginMessage">[+login_message+]</p>
        <div class="sectionBody">
            <label for="username" class="username">[+username+]:<br />
            <input type="text" class="text" name="username" id="username" tabindex="1" value="[+uid+]" /></label>
            <label for="password" class="password">[+password+]:<br />
            <input type="password" class="text" name="password" id="password" tabindex="2" value="" /></label>
            <div class="captcha">
            <p class="caption">[+login_captcha_message+]</p>
            [+captcha_image+]
            [+captcha_input+]
            </div>
            <label for="rememberme" class="rememberme"><input type="checkbox" id="rememberme" name="rememberme" tabindex="4" value="1" class="checkbox" [+remember_me+] />[+remember_username+]</label>
            <input type="submit" class="login button" id="submitButton" value="[+login_button+]" />
            <!-- anything to output before the login box via a plugin ... like the forgot password link? -->
            [+OnManagerLoginFormRender+]
        </div>
    </form>
    <div class="loginLicense">
    &copy; 2005-2013 by <a href="http://modx.com/" target="_blank">MODX&reg;</a>, and licensed under the <strong>GPL</strong>.
    </div>
</div>
<!-- close #mx_loginbox -->

<!-- convert this to a language include -->
</body>
</html>