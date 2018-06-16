<div class="stepcontainer">
    <ul class="progressbar">
        <li class="visited">[%choose_language%]</li>
        <li class="active">[%installation_mode%]</li>
        <li>[%optional_items%]</li>
        <li>[%preinstall_validation%]</li>
        <li>[%install_results%]</li>
    </ul>
    <div class="clearleft"></div>
</div>
<form name="install" id="install_form" action="index.php?action=options" method="post">
    <input type="hidden" value="[+install_language+]" name="language" />
    <input type="hidden" value="1" name="chkagree" [+checkedChkagree+] />
    <input type="hidden" value="[+installMode+]" name="installmode" />
    <input type="hidden" value="[+database_connection_method+]" name="database_connection_method" />
    <h2>[%connection_screen_database_info%]</h2>
    <h3>[%connection_screen_server_connection_information%]</h3>
    <p>[%connection_screen_server_connection_note%]</p>
    <p class="labelHolder">
        <label for="databasehost">[%connection_screen_database_host%]</label>
        <input id="databasehost" value="[+databasehost+]" name="databasehost" />
    </p>
    <p class="labelHolder">
        <label for="databaseloginname">[%connection_screen_database_login%]</label>
        <input id="databaseloginname" name="databaseloginname" value="[+databaseloginname+]" />
    </p>
    <p class="labelHolder">
        <label for="databaseloginpassword">[%connection_screen_database_pass%]</label>
        <input id="databaseloginpassword" type="password" name="databaseloginpassword" value="[+databaseloginpassword+]" />
    </p>
    <!-- connection test action/status message -->
    <div class="clickHere">
        &rarr; <a id="servertest" href="javascript:void(0);">[%connection_screen_server_test_connection%]</a>
    </div>
    <div class="status" id="serverstatus"></div>
    <!-- end connection test action/status message -->
    <div id="setCollation" style="padding-top:2em;">
        <div id="collationMask">
            <h3>[%connection_screen_database_connection_information%]</h3>
            <p>[%connection_screen_database_connection_note%]</p>
            <p class="labelHolder">
                <label for="database_name">[%connection_screen_database_name%]</label>
                <input id="database_name" value="[+database_name+]" name="database_name" />
            </p>
            <p class="labelHolder">
                <label for="tableprefix">[%connection_screen_table_prefix%]</label>
                <input id="tableprefix" value="[+tableprefix+]" name="tableprefix" />
            </p>
            <p class="labelHolder" style="display:[+show#connection_method+]">
                <label for="database_connection_method">[%connection_screen_connection_method%]</label>
                <div id="connection_method" name="connection_method">
                    <select id="database_connection_method" name="database_connection_method">
                        <option value="SET CHARACTER SET" [+selected_set_character_set+]>SET CHARACTER SET</option>
                        <option value="SET NAMES" [+selected_set_names+]>SET NAMES</option>
                    </select>
                </div>
            </p>
            <p class="labelHolder">
                <label for="database_collation">[%connection_screen_collation%]</label>
                <div id="collation">
                    <select id="database_collation" name="database_collation">
                        <option value="[+database_collation+]" selected="selected">[+database_collation+]</option>
                    </select>
                </div>
            </p>
            <div class="clickHere">
                &rarr; <a id="databasetest" href="javascript:void(0);">[%connection_screen_database_test_connection%]</a>
            </div>
            <div class="status" id="databasestatus">&nbsp;</div>
        </div>
    </div>
    <div id="AUH" style="display:[+show#AUH+];margin-top:1.5em;">
        <div id="AUHMask">
            <h2>[%connection_screen_defaults%]</h2>
            <h3>[%connection_screen_default_admin_user%]</h3>
            <p>[%connection_screen_default_admin_note%]</p>
            <p class="labelHolder">
                <label for="cmsadmin">[%connection_screen_default_admin_login%]</label>
                <input id="cmsadmin" value="[+cmsadmin+]" name="cmsadmin" />
            </p>
            <p class="labelHolder">
                <label for="cmsadminemail">[%connection_screen_default_admin_email%]</label>
                <input id="cmsadminemail" value="[+cmsadminemail+]" name="cmsadminemail" style="width:250px;" />
            </p>
            <p class="labelHolder">
                <label for="cmspassword">[%connection_screen_default_admin_password%]</label>
                <input id="cmspassword" type="password" name="cmspassword" value="[+cmspassword+]" />
            </p>
            <p class="labelHolder">
                <label for="cmspasswordconfirm">[%connection_screen_default_admin_password_confirm%]</label>
                <input id="cmspasswordconfirm" type="password" name="cmspasswordconfirm" value="[+cmspasswordconfirm+]" />
            </p>
            <h3 style="margin-top:2em">[%default_language%]</h3>
            <p>[%default_language_description%]</p>
            <p class="labelHolder">
                <label for="managerlanguage_select">&nbsp;</label>
                <select name="managerlanguage" id="managerlanguage_select">[+managerLangs+]</select>
                <br />
                <br />
            </p>
        </div>
    </div>
    <p class="buttonlinks">
        <a href="javascript:void(0);" class="prev" id="prevlink" title="[%btnback_value%]"><span>[%btnback_value%]</span></a>
        <a style="display:none;" id="nextlink" href="javascript:void(0);" title="[%btnnext_value%]"><span>[%btnnext_value%]</span></a>
    </p>
</form>
<script type="text/javascript" src="../[+MGR_DIR+]/media/script/mootools/mootools.js"></script>
<script type="text/javascript">
language = '[+install_language+]';
installMode = '[+installMode+]';
</script>
<script type="text/javascript">
function testServer() {
    // get the server test status as soon as collation received
    var url = "index.php?self=1&action=connection/servertest";

    host = $('databasehost').value;
    uid = $('databaseloginname').value;
    pwd = $('databaseloginpassword').value;

    var pars = Object.toQueryString({
        q: url,
        host: host,
        uid: uid,
        pwd: pwd,
        language: language
    });

    new Ajax(url, { postBody: pars, update: $('serverstatus'), onComplete: setColor }).request();
}

function setDefaults() {
    if ($('database_pass') !== null && document.getElementById('AUH')) {
        window.Slider2.slideIn();
        var Slider2FX = new Fx.Styles('AUHMask', { duration: 997, transition: Fx.Transitions.linear });
        Slider2FX.start({ 'opacity': [0, 1] });
        window.setTimeout("$('AUH').style.backgroundColor = '#ffffff';", 1000);
        Slider2Scroll = new Fx.Scroll(window);
        Slider2Scroll.toElement('managerlanguage_select');
    }
}

function setColor() {
    var col = $('database_collation');

    ss = document.getElementById('serverstatus');
    ssv = ss.innerHTML;
    if ($('server_pass') !== null) {
        col.setStyle('background-color', '#9CCD00');
        col.setStyle('border-width', '1px');
        col.setStyle('font-weight', 'bold');

        window.Slider1.slideIn(); //toggle the slider up and down.
        var Slider1FX = new Fx.Styles('collationMask', { duration: 997, transition: Fx.Transitions.linear });
        Slider1FX.start({ 'opacity': [0, 1] });
        window.setTimeout("$('setCollation').style.backgroundColor = '#ffffff';", 1000);
        Slider1Scroll = new Fx.Scroll(window);
        Slider1Scroll.toElement('databasestatus');
        $('database_name').focus();
    } else {
        window.Slider1.slideOut(); //toggle the slider up and down.
        $('collationMask').setStyle('opacity', 0);
    }
}

var f = document.install;
// get collation from the database server
$('servertest').addEvent('click', function(e) {
    e = new Event(e).stop();
    if (f.databasehost.value == "") {
        alert("[%alert_enter_host%]");
        f.databasehost.focus();
        return false;
    }
    if (f.databaseloginname.value == "") {
        alert("[%alert_enter_login%]");
        f.databaseloginname.focus();
        return false;
    }

    var url = "index.php?self=1&action=connection/collation";

    host = $('databasehost').value;
    uid = $('databaseloginname').value;
    pwd = $('databaseloginpassword').value;

    var pars = Object.toQueryString({
        q: url,
        host: host,
        uid: uid,
        pwd: pwd,
        language: language
    });

    new Ajax(url, { postBody: pars, update: $('collation'), onComplete: testServer }).request();
});

// database test
$('databasetest').addEvent('click', function(e) {
    e = new Event(e).stop();
    if (f.database_name.value == "") {
        alert("[%alert_enter_database_name%]");
        f.database_name.focus();
        return false;
    }

    var url = "index.php?self=1&action=connection/databasetest";

    host = $('databasehost').value;
    uid = $('databaseloginname').value;
    pwd = $('databaseloginpassword').value;
    database_name = $('database_name').value;
    tableprefix = $('tableprefix').value;
    database_collation = $('database_collation').getValue();
    database_connection_method = $('database_connection_method').value;

    var pars = Object.toQueryString({
        q: url,
        host: host,
        uid: uid,
        pwd: pwd,
        database_name: database_name,
        tableprefix: tableprefix,
        database_collation: database_collation,
        database_connection_method: database_connection_method,
        language: language,
        installMode: installMode
    });
    new Ajax(url, { postBody: pars, update: $('databasestatus'), onComplete: setDefaults }).request();
    $('nextlink').style.display = 'inline';
});


Slider1 = new Fx.Slide('setCollation', { duration: 477 }); //transition:Fx.Sine.easeOut,
Slider1.hide();
$('setCollation').style.backgroundColor = '#ffff00';
$('setCollation').style.display = 'block';
if ((installMode == 0 || installMode == 2) && document.getElementById('AUH')) {
    Slider2 = new Fx.Slide('AUH', { duration: 477 }); //transition:Fx.Sine.easeOut,
    Slider2.hide();
    $('AUH').style.display = 'block';
    $('AUH').style.backgroundColor = '#ffff00';
}

$('prevlink').addEvent('click', function(e) {
    document.getElementById('install_form').action = 'index.php?action=mode';
    document.getElementById('install_form').submit();
});

$('nextlink').addEvent('click', function(e) {
    var alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if (alpha.indexOf(f.tableprefix.value.charAt(0), 0) == -1) {
        alert("[%alert_table_prefixes%]");
        f.tableprefix.focus();
        return false;
    }
    dbs = document.getElementById('databasestatus');
    dbsv = dbs.innerHTML;
    if (dbsv.length == 0 || dbsv == '&nbsp;') {
        alert("[%alert_database_test_connection%]");
        return false;
    }
    if (dbsv.indexOf("failed") >= 0) {
        alert("[%alert_database_test_connection_failed%]");
        return false;
    }
    if (f.cmsadmin && f.cmsadmin.value == "") {
        alert("[%alert_enter_adminlogin%]");
        f.cmsadmin.focus();
        return false;
    }
    if (f.cmspassword && f.cmspassword.value == "") {
        alert("[%alert_enter_adminpassword%]");
        f.cmspassword.focus();
        return false;
    }
    if (f.cmspassword && f.cmspassword.value != f.cmspasswordconfirm.value) {
        alert("[%alert_enter_adminconfirm%]");
        f.cmspassword.focus();
        return false;
    }
    document.getElementById('install_form').action = 'index.php?action=options';
    document.getElementById('install_form').submit();
});
</script>
