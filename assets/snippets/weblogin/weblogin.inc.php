<?php
# WebLogin 1.0
# Created By Raymond Irving 2004
#::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

defined('IN_PARSER_MODE') or die();

# load tpl
if(is_numeric($tpl)) $tpl = ($doc=$modx->getDocuments($tpl)) ? $doc['content']:"Document '$tpl' not found.";
else if($tpl) $tpl = ($chunk=$modx->getChunk($tpl)) ? $chunk:"Chunk '$tpl' not found.";
if(!$tpl) $tpl = getWebLogintpl();

// extract declarations
$declare = webLoginExtractDeclarations($tpl);
$tpls = explode((isset($declare["separator"]) ? $declare["separator"]:"<!--tpl_separator-->"),$tpl);

if(!isset($_SESSION['webValidated'])){
    ob_start();
//    if(isset($_COOKIE[$cookieKey])) {
//        $cookieSet = 1;
//        $sitename = $_COOKIE[$cookieKey];
//        $thepasswd = substr($site_id,-5)."crypto"; // create a password based on site id
//        $rc4 = new rc4crypt;
//        $thestring = $rc4->endecrypt($thepasswd,$sitename,'de');
//        $uid = $thestring;
//    }else{
        $uid = isset($_POST['username'])? $modx->db->escape(htmlspecialchars(trim($_POST['username']), ENT_QUOTES)):'';
//    }
    ?>
    <script type="text/JavaScript">
    <!--//--><![CDATA[//><!--
        function getElementById(id){
            var o, d=document;
            if (d.layers) {o=d.layers[id];if(o) o.style=o};
            if (!o && d.getElementById) o=d.getElementById(id);
            if (!o && d.all) o = d.all[id];
            return o;
        }
    
        function webLoginShowForm(i){
            var a = getElementById('WebLoginLayer0');
            var b = getElementById('WebLoginLayer2');
            if(i==1 && a && b) {
                a.style.display="block";
                b.style.display="none";
                document.forms['loginreminder'].txtpwdrem.value = 0;
            }
            else if(i==2 && a && b) {
                a.style.display="none";
                b.style.display="block";    
                document.forms['loginreminder'].txtpwdrem.value = 1;
            }
        };        
        function webLoginCheckRemember () {
            if(document.loginfrm.rememberme.value==1) {
                document.loginfrm.rememberme.value=0;    
            } else {
                document.loginfrm.rememberme.value=1;
            }
        }
        function webLoginEnter(nextfield,event) {
            if(event && event.keyCode == 13) {
                if(nextfield.name=='cmdweblogin') {
                    document.loginfrm.submit();
                    return false; 
                }
                else {
                    nextfield.focus();
                    return false; 
                }
            } else {
                return true;
            }
        }
    //--><!]]>
    </script>
    <?php
        
        // display login
        $ref = isset($_REQUEST["refurl"]) ? array("refurl" => urlencode($_REQUEST["refurl"])) : array();
        $tpl = "<div id='WebLoginLayer0' style='position:relative'>".$tpls[0]."</div>";
        $tpl.= "<div id='WebLoginLayer2' style='position:relative;display:none'>".$tpls[2]."</div>";
        $tpl = str_replace("[+action+]",preserveUrl($modx->documentIdentifier,"",$ref),$tpl);
        $tpl = str_replace("[+rememberme+]",(isset($cookieSet) ? 1 : 0),$tpl);    
        $tpl = str_replace("[+username+]",$uid,$tpl);    
        $tpl = str_replace("[+checkbox+]",(isset($cookieSet) ? "checked" : ""),$tpl);
        $tpl = str_replace("[+logintext+]",$loginText,$tpl);    
        echo $tpl;
    ?>
    <script type="text/javascript">
        if (document.loginfrm) <?php echo !empty($uid) ? "document.loginfrm.password.focus()" : "document.loginfrm.username.focus()" ?>;
    </script>
    <?php
    $output .= ob_get_contents();
    ob_end_clean();
} else {
    $output= '';
    $dbase = $modx->dbConfig['dbase'];
    $table_prefix = $modx->dbConfig['table_prefix'];
    
    if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
    else $ip = "UNKNOWN";$_SESSION['ip'] = $ip;
    
    $itemid = isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : 'NULL' ;$lasthittime = time();$a = 998;
    
    if($a!=1) {
        $sql = "REPLACE INTO $dbase.`".$table_prefix."active_users` (internalKey, username, lasthit, action, id, ip) values(-".$_SESSION['webInternalKey'].", '".$_SESSION['webShortname']."', '".$lasthittime."', '".$a."', ".$itemid.", '$ip')";
        if(!$rs = $modx->db->query($sql)) {
            $output = "error replacing into active users! SQL: ".$sql;
            return;
        }
    }
    
    // display logout
    $tpl = $tpls[1];
    $url = preserveUrl($modx->documentObject['id']);
    $url = $url.((strpos($url,"?")===false) ? "?":"&amp;")."webloginmode=lo";
    $tpl = str_replace("[+action+]",$url,$tpl);    
    $tpl = str_replace("[+logouttext+]",$logoutText,$tpl);    
    $output .= $tpl;
}

# Returns Default WebLogin tpl
function getWebLogintpl(){
    ob_start();
    ?>
    <!-- #declare:separator <hr> --> 
    <!-- login form section-->
    <form method="post" name="loginfrm" action="[+action+]" style="margin: 0px; padding: 0px;"> 
    <input type="hidden" value="[+rememberme+]" name="rememberme" /> 
    <table border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td>
    <table border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><b>User:</b></td>
        <td><input type="text" name="username" tabindex="1" onkeypress="return webLoginEnter(document.loginfrm.password);" size="8" style="width: 150px;" value="[+username+]" /></td>
      </tr>
      <tr>
        <td><b>Password:</b></td>
        <td><input type="password" name="password" tabindex="2" onkeypress="return webLoginEnter(document.loginfrm.cmdweblogin);" style="width: 150px;" value="" /></td>
      </tr>
      <tr>
        <td><label for="chkbox" style="cursor:pointer">Remember me:&nbsp; </label></td>
        <td>
        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><input type="checkbox" id="chkbox" name="chkbox" tabindex="4" size="1" value="" [+checkbox+] onclick="webLoginCheckRemember()" /></td>
            <td align="right">                                    
            <input type="submit" value="[+logintext+]" name="cmdweblogin" /></td>
          </tr>
        </table>
        </td>
      </tr>
      <tr>
        <td colspan="2"><a href="#" onclick="webLoginShowForm(2);return false;">Forget Password?</a></td>
      </tr>
    </table>
    </td>
    </tr>
    </table>
    </form>
    <hr>
    <!-- log out hyperlink section -->
    <a href='[+action+]'>[+logouttext+]</a>
    <hr>
    <!-- Password reminder form section -->
    <form name="loginreminder" method="post" action="[+action+]" style="margin: 0px; padding: 0px;">
    <input type="hidden" name="txtpwdrem" value="0" />
    <table border="0">
        <tr>
          <td>Enter the email address of your account <br />below to receive your password:</td>
        </tr>
        <tr>
          <td><input type="text" name="txtwebemail" size="24" /></td>
        </tr>
        <tr>
          <td align="right"><input type="submit" value="Submit" name="cmdweblogin" />
          <input type="reset" value="Cancel" name="cmdcancel" onclick="webLoginShowForm(1);" /></td>
        </tr>
      </table>
    </form>
    <?php 
    $t = ob_get_contents();
    ob_end_clean();
    return $t;
}
?>