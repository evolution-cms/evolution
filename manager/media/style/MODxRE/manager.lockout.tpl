<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>MODx Content Manager</title>
<meta http-equiv="content-type" content="text/html; charset=[+modx_charset+]" />
<meta name="robots" content="noindex, nofollow" />
<style type="text/css">
    /* Neutralize styles, fonts and viewport:
    ---------------------------------------------------------------- */
    html, body, form, fieldset {margin: 0;padding: 0;}
    html {
    font-size: 100.01%; /* avoids obscure font-size bug */
    line-height: 1.5; /* http://meyerweb.com/eric/thoughts/2006/02/08/unitless-line-heights/ */
    font-family: "Lucida Grande", Helvetica, Arial, sans-serif !important; /* IE ignores this and renders Arial better */
    font-family: Arial, Tahoma, Helvetica, sans-serif;height: 100%;color: #333;}
    body {font-size: 75%; /* 12px 62.5% for 10px*/margin-bottom: 1px; /* avoid jumping scrollbars */background: #F4F4F4; text-shadow: 0 1px 0 #fff;}
    h1 {padding:0;margin:0;font-weight:normal;font-size:218%;}
    form {border: 5px solid #EAECEE;padding:10px;}
    .sectionHeader {padding: 5px 3px 5px 18px;font-weight: bold;color: #000;background: #EAECEE url(media/style/[+theme+]/images/misc/fade.gif) repeat-x top;}
    .sectionBody {padding:20px;display:block;background: #fff;}
    #mx_loginbox {width: 460px;margin:30px auto 0;}
    fieldset.buttonset{text-align:center;border:none;padding-top:20px;}
    .loginLicense {width: 460px;color: #B2B2B2;margin: 0.5em auto;font-size: 90%;padding-left: 20px;}
    .loginLicense a {color: #B2B2B2;}
    .notice {width: 100%;padding: 5px;border: 1px solid #eee;background-color: #F4F4F4;color: #707070;}
    #preLoader {position: absolute;z-index: 50000;width: 100%;height: 100%;text-align: center;vertical-align: middle;}
    .loginMessage {font-size:11px;color: #000;}
</style>

<script type="text/javascript">
    function doLogout() {
        top.location = '[+logouturl+]';
    }

    function gotoHome() {
        top.location = '[+homeurl+]';
    }
</script>

<script type="text/javascript">
/* <![CDATA[ */
    if (top.frames.length!=0) {
        top.location=self.document.location;
    }
/* ]]> */
</script>

</head>
<body id="login">
<!-- start the login box -->
<div id="mx_loginbox">

<form method="post" name="loginfrm" id="loginfrm" action="processors/login.processor.php">

    <!-- the logo -->
    <div class="sectionHeader">
        <img src='media/style/[+theme+]/images/misc/login-logo.png' alt='[+logo_slogan+]' />
    </div>
    <!-- end #mx_logobox -->



    <div class="sectionBody">
        <h1 class="siteName">[+site_name+]</h1>

        <div class="loginMessage">[+manager_lockout_message+]</div>

		<fieldset class="buttonset">

        <input type="button" class="login" id="submitButton" value="[+home+]" onclick="return gotoHome();"  />&nbsp;
        <input type="button" class="login" id="submitButton" value="[+logout+]" onclick="return doLogout();" />
        </fieldset>
    </div>

    <br style="clear:both;height: 1px"/>

</form>
</div>
<!-- close #mx_loginbox -->


<!-- convert this to a language include -->
<p class="loginLicense">
    <strong>MODx</strong>&trade; is licensed under the GPL license. &copy; 2005-2013 <a href="http://modx.com/" target="_blank">MODx</a>.
</p>


</body>
</html>
