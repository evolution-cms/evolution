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
    <h2>[%connection_screen_database_info%]</h2>
    <h3>[%connection_screen_server_connection_information%]</h3>
    <p>[%connection_screen_server_connection_note%]</p>
    <p class="labelHolder">
        <label for="database_type">[%connection_screen_database_type%]</label>
        <select id="database_type" name="database_type">
            [+databaseTypeOptions+]
        </select>
    </p>
    <p class="labelHolder">
        <label for="databasehost">[%connection_screen_database_host%]</label>
        <input type="text" id="databasehost" value="[+databasehost+]" name="databasehost" />
        <small class="is-invalid">[%alert_enter_host%]</small>
    </p>
    <p class="labelHolder">
        <label for="databaseloginname">[%connection_screen_database_login%]</label>
        <input type="text" id="databaseloginname" name="databaseloginname" value="[+databaseloginname+]" />
        <small class="is-invalid">[%alert_enter_login%]</small>
    </p>
    <p class="labelHolder">
        <label for="databaseloginpassword">[%connection_screen_database_pass%]</label>
        <input type="text" id="databaseloginpassword" name="databaseloginpassword" value="[+databaseloginpassword+]" />
    </p>
    <!-- connection test action/status message -->
    <div class="clickHere">
        &rarr; <button id="servertest">[%connection_screen_server_test_connection%]</button>
    </div>
    <div class="status" id="serverstatus"></div>
    <!-- end connection test action/status message -->
    <div id="setCollation" class="pt-5">
        <div id="collationMask">
            <h3>[%connection_screen_database_connection_information%]</h3>
            <p>[%connection_screen_database_connection_note%]</p>
            <p class="labelHolder">
                <label for="database_name">[%connection_screen_database_name%]</label>
                <input type="text" id="database_name" value="[+database_name+]" name="database_name" />
                <small class="is-invalid">[%alert_enter_database_name%]</small>
            </p>
            <p class="labelHolder">
                <label for="tableprefix">[%connection_screen_table_prefix%]</label>
                <input type="text" id="tableprefix" value="[+tableprefix+]" name="tableprefix" />
                <small class="is-invalid">[%alert_table_prefixes%]</small>
            </p>
            <p class="labelHolder">
                <label for="database_collation">[%connection_screen_collation%]</label>
                <span id="collation">
                    <select id="database_collation" name="database_collation">
                        <option value="[+database_collation+]" selected="selected">[+database_collation+]</option>
                    </select>
                </span>
            </p>
            <div class="clickHere">
                &rarr; <button id="databasetest">[%connection_screen_database_test_connection%]</button>
            </div>
            <div class="status" id="databasestatus">&nbsp;</div>
        </div>
    </div>
    <div id="AUH" class="mt-4" [+show#AUH+]>
        <div id="AUHMask">
            <h2>[%connection_screen_defaults%]</h2>
            <h3>[%connection_screen_default_admin_user%]</h3>
            <p>[%connection_screen_default_admin_note%]</p>
            <p class="labelHolder">
                <label for="cmsadmin">[%connection_screen_default_admin_login%]</label>
                <input type="text" id="cmsadmin" value="[+cmsadmin+]" name="cmsadmin" />
                <small class="is-invalid">[%alert_enter_adminlogin%]</small>
            </p>
            <p class="labelHolder">
                <label for="cmsadminemail">[%connection_screen_default_admin_email%]</label>
                <input type="text" id="cmsadminemail" value="[+cmsadminemail+]" name="cmsadminemail" />
            </p>
            <p class="labelHolder">
                <label for="cmspassword">[%connection_screen_default_admin_password%]</label>
                <input type="password" id="cmspassword" name="cmspassword" value="[+cmspassword+]" />
                <small class="is-invalid" data-default="[%alert_enter_adminpassword%]" data-minlength="[+adminPasswordMinLengthMessage+]">[%alert_enter_adminpassword%]</small>
            </p>
            <p class="labelHolder">
                <label for="cmspasswordconfirm">[%connection_screen_default_admin_password_confirm%]</label>
                <input type="password" id="cmspasswordconfirm" name="cmspasswordconfirm" value="[+cmspasswordconfirm+]" />
                <small class="is-invalid" data-default="[%alert_enter_adminconfirm%]" data-minlength="[+adminPasswordMinLengthMessage+]">[%alert_enter_adminconfirm%]</small>
            </p>
            <h3 class="mt-5">[%default_language%]</h3>
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
        <button type="button" id="prevlink" title="[%btnback_value%]" class="prev"><span>[%btnback_value%]</span></button>
        <button type="button" id="nextlink" title="[%btnnext_value%]"><span>[%btnnext_value%]</span></button>
    </p>
</form>
<script type="text/javascript" nonce="[+csrf_nonce+]">
    var form = document.install;
    var language = '[+install_language+]';
    var installMode = parseInt('[+installMode+]');

    function resetFieldMessage(input) {
        var help = input?.parentElement?.querySelector('.is-invalid');
        if (help?.dataset?.default) {
            help.textContent = help.dataset.default;
        }
    }

    function setFieldErrorMessage(input, messageType) {
        var help = input?.parentElement?.querySelector('.is-invalid');
        if (help?.dataset?.[messageType]) {
            help.textContent = help.dataset[messageType];
        }
        input.parentElement.classList.add('has-error');
        input.focus();
        return false;
    }

    document.querySelectorAll('[name]').forEach(function(el) {
        el.onkeyup = function() {
            if (this.value === '') {
                this.parentElement.classList.add('has-error');
                resetFieldMessage(this);
            } else {
                this.parentElement.classList.remove('has-error');
                resetFieldMessage(this);
            }
        };
        el.onchange = function() {
            if (this.value === '') {
                this.parentElement.classList.add('has-error');
                resetFieldMessage(this);
            } else {
                this.parentElement.classList.remove('has-error');
                resetFieldMessage(this);
            }
        };
    });

    // Toggle server credential fields based on database type
    function toggleServerCredentials() {
        var type = document.getElementById('database_type').value;
        var isSqlite = (type === 'sqlite');
        var serverFields = ['databasehost', 'databaseloginname', 'databaseloginpassword'];
        serverFields.forEach(function(id) {
            var el = document.getElementById(id);
            var p = el.parentElement;
            p.style.display = isSqlite ? 'none' : 'block';
            if (isSqlite) {
                p.classList.remove('has-error');
                el.value = '';
            }
        });
    }

    document.getElementById('database_type').addEventListener('change', toggleServerCredentials);
    // Initial toggle on load
    toggleServerCredentials();

    // get collation from the database server
    document.getElementById('servertest').addEventListener('click', function(e) {
        e.preventDefault();

        var type = form.database_type.value;
        if (type !== 'sqlite') {
            if (form.databasehost.value === '') {
                form.databasehost.parentElement.classList.add('has-error');
                form.databasehost.focus();
                return false;
            }

            if (form.databaseloginname.value === '') {
                form.databaseloginname.parentElement.classList.add('has-error');
                form.databaseloginname.focus();
                return false;
            }
        }

        var url = 'index.php?s=1&action=connection/collation';

        new Ajax(url, {
            data: {
                q: url,
                host: form.databasehost.value,
                database_type: form.database_type.value,
                uid: form.databaseloginname.value,
                pwd: form.databaseloginpassword.value,
                database_name: form.database_name.value,
                language: language
            },
            success: testServer
        });
    });

    // database test
    document.getElementById('databasetest').addEventListener('click', function(e) {
        e.preventDefault();

        if (form.database_name.value === '') {
            form.database_name.parentElement.classList.add('has-error');
            form.database_name.focus();
            return false;
        }

        var url = 'index.php?s=1&action=connection/databasetest';

        new Ajax(url, {
            data: {
                q: url,
                host: form.databasehost.value,
                database_type: form.database_type.value,
                uid: form.databaseloginname.value,
                pwd: form.databaseloginpassword.value,
                database_name: form.database_name.value,
                tableprefix: form.tableprefix.value,
                database_collation: form.database_collation.value,
                language: language,
                installMode: installMode
            },
            success: setDefaults
        });

        document.getElementById('nextlink').style.display = 'inline-block';
    });
    document.getElementById('nextlink').style.display = 'none';

    document.getElementById('setCollation').style.backgroundColor = '#ffff00';
    document.getElementById('setCollation').style.display = 'none';

    if ((installMode === 0 || installMode === 2) && document.getElementById('AUH')) {
        document.getElementById('AUH').style.display = 'none';
        document.getElementById('AUH').style.backgroundColor = '#ffff00';
    }

    document.getElementById('prevlink').addEventListener('click', function(e) {
        form.action = 'index.php?action=mode';
        form.submit();
    });

    document.getElementById('nextlink').addEventListener('click', function(e) {
        var alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (alpha.indexOf(form.tableprefix.value.charAt(0), 0) === -1) {
            form.tableprefix.parentElement.classList.add('has-error');
            form.tableprefix.focus();
            return false;
        }
        var dbs = document.getElementById('databasestatus');
        var dbsv = dbs.innerHTML;
        if (dbsv.length === 0 || dbsv === '&nbsp;') {
            alert('[%alert_database_test_connection%]');
            return false;
        }
        if (dbsv.indexOf('failed') >= 0) {
            alert('[%alert_database_test_connection_failed%]');
            return false;
        }
        if (form.cmsadmin && form.cmsadmin.value === '') {
            form.cmsadmin.parentElement.classList.add('has-error');
            form.cmsadmin.focus();
            return false;
        }
        if (form.cmspassword?.value?.length < 8) {
            return setFieldErrorMessage(form.cmspassword, 'minlength');
        }
        if (form.cmspasswordconfirm?.value?.length < 8) {
            return setFieldErrorMessage(form.cmspasswordconfirm, 'minlength');
        }
        if (form.cmspasswordconfirm?.value !== form.cmspassword?.value) {
            return setFieldErrorMessage(form.cmspasswordconfirm, 'default');
        }
        form.action = 'index.php?action=options';
        form.submit();
    });

    function testServer()
    {
        if (arguments[0]) {
            document.getElementById('collation').innerHTML = arguments[0];
        }
        // get the server test status as soon as collation received
        var url = 'index.php?s=1&action=connection/servertest';

        new Ajax(url, {
            data: {
                q: url,
                host: form.databasehost.value,
                database_type: form.database_type.value,
                uid: form.databaseloginname.value,
                pwd: form.databaseloginpassword.value,
                language: language
            },
            success: setColor
        });
    }

    function setDefaults()
    {
        if (arguments[0]) {
            document.getElementById('databasestatus').innerHTML = arguments[0];
        }
        if (document.getElementById('database_pass') !== null && document.getElementById('AUH')) {
            document.getElementById('AUH').style.display = 'block';
            document.getElementById('AUHMask').style.opacity = '1';
            window.setTimeout(function() {
                document.getElementById('AUH').style.backgroundColor = '#ffffff';
            }, 1000);
        }
    }

    function setColor()
    {
        if (arguments[0]) {
            document.getElementById('serverstatus').innerHTML = arguments[0];
        }
        var col = document.getElementById('database_collation');
        var ss = document.getElementById('serverstatus');
        var ssv = ss.innerHTML;
        if (document.getElementById('server_pass') !== null) {


            document.getElementById('setCollation').style.display = 'block';
            document.getElementById('collationMask').style.opacity = '1';
            document.getElementById('database_collation').style.backgroundColor = '#9CCD00';
            document.getElementById('database_collation').style.borderWidth = '1px';
            document.getElementById('database_collation').style.fontWeight = 'bold';
            window.setTimeout(function() {
                document.getElementById('setCollation').style.backgroundColor = '#ffffff';
            }, 1000);
            document.getElementById('database_name').focus();
        } else {
            document.getElementById('setCollation').style.display = 'none';
            document.getElementById('collationMask').style.opacity = '0';
        }
    }

    function objToQueryString(obj)
    {
        return '?' +
            Object.keys(obj).map(function(key) {
                return encodeURIComponent(key) + '=' +
                    encodeURIComponent(obj[key]);
            }).join('&');
    }

    var Ajax = (function() {
        return (function() {
            var url = arguments[0] && arguments[0][0] || '';
            var data = arguments[0] && arguments[0][1] || {};
            if (url) {
                if (typeof data.data === 'object') {
                    data.data = objToQueryString(data.data);
                }

                var xhr = new XMLHttpRequest();
                xhr.open('post', url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (this.readyState === 4) {
                        if (typeof data.update !== 'undefined') {
                            data.update.innerHTML = this.response;
                        }
                        if (typeof data.success === 'function') {
                            data.success(this.response);
                        }
                    }
                };
                xhr.send(data.data);
            }
        })(arguments);
    });
</script>
