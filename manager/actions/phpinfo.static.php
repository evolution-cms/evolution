<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
?>

<style type="text/css">
    body {padding:20px;}
    pre {margin: 0px; font-family: monospace;}
    a:link {color: #000099; text-decoration: none; background-color: #f7f7f7;}
    a:hover {text-decoration: underline;}
    table {margin-top:20px;border-collapse: collapse;background-color:#ffffff;}
    .center {text-align: center;}
    .center table { margin-left: auto; margin-right: auto; text-align: left;}
    .center th { text-align: center !important; }
    td, th { border: 1px solid #999999; vertical-align: baseline;padding:4px;}
    h1 {text-align:left;margin:10px auto;}
    h2 {text-align:left;margin:10px auto;}
    .p {text-align: left;}
    .e {width:150px;background-color: #eeeeee; color: #333333;}
    .h {background-color: #bcbcd6; font-weight: bold; color: #333333;}
    .h h1 {width:90%;font-size:20px;}
    .v {width:400px;color: #333333;}
    .vr {background-color: #cccccc; text-align: right; color: #333333;}
    img {float: right; border: 0px;}
    hr {background-color: #cccccc; border: 0px; height: 1px; color: #333333;}
</style>

<?php
ob_start();
phpinfo();
$pinfo = ob_get_contents();
ob_end_clean();
$pinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $pinfo);
$pinfo = str_replace('<div class="center">', '<div>', $pinfo);
$pinfo = str_replace('width="600"', 'width="90%"', $pinfo);
$pinfo = str_replace('src,input', 'src, input', $pinfo);
echo $pinfo;
?>