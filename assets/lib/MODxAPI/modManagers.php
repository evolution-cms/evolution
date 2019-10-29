<?php
require_once('MODx.php');

/**
 * Class modUsers
 */
class modManagers extends MODxAPI
{
    /**
     * @var array
     */
    protected $default_field = array(
        'user'      => array(
            'username' => '',
            'password' => '',
        ),
        'attribute' => array(
            'fullname'         => '',
            'role'             => 0,
            'email'            => '',
            'phone'            => '',
            'mobilephone'      => '',
            'blocked'          => 0,
            'blockeduntil'     => 0,
            'blockedafter'     => 0,
            'logincount'       => 0,
            'lastlogin'        => 0,
            'thislogin'        => 0,
            'failedlogincount' => 0,
            'sessionid'        => '',
            'dob'              => 0,
            'gender'           => 0,
            'country'          => '',
            'state'            => '',
            'city'             => '',
            'street'           => '',
            'zip'              => '',
            'fax'              => '',
            'photo'            => '',
            'comment'          => '',
            'createdon'        => 0,
            'editedon'         => 0
        ),
        'hidden'    => array(
            'internalKey'
        )
    );

    /**
     * @var string
     */
    protected $givenPassword = '';
    protected $groupIds = array();
    protected $mgrPermissions = array();

    /**
     * @var integer
     */
    private $rememberTime;

    /**
     * MODxAPI constructor.
     * @param DocumentParser $modx
     * @param bool $debug
     * @throws Exception
     */
    public function __construct(DocumentParser $modx, $debug = false)
    {
        $this->setRememberTime(60 * 60 * 24 * 365 * 5);
        parent::__construct($modx, $debug);
        $this->modx->loadExtension('phpass');
    }

    /**
     * @param $val
     * @return $this
     */
    protected function setRememberTime($val){
        $this->rememberTime = (int)$val;
        return $this;
    }

    /**
     * @return integer
     */
    public function getRememberTime(){
        return $this->rememberTime;
    }

    /**
     * @param $key
     * @return bool
     */
    public function issetField($key)
    {
        return (array_key_exists($key, $this->default_field['user']) || array_key_exists($key,
                $this->default_field['attribute']) || in_array($key, $this->default_field['hidden']));
    }

    /**
     * @param string $data
     * @return string|false
     */
    protected function findUser($data)
    {
        switch (true) {
            case (is_int($data) || ((int)$data > 0 && (string)intval($data) === $data)):
                $find = 'attribute.internalKey';
                break;
            case filter_var($data, FILTER_VALIDATE_EMAIL):
                $find = 'attribute.email';
                break;
            case is_scalar($data):
                $find = 'user.username';
                break;
            default:
                $find = false;
        }

        return $find;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function create($data = array())
    {
        parent::create($data);
        $this->set('createdon', time());

        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function edit($id)
    {
        $id = is_scalar($id) ? trim($id) : '';
        if ($this->getID() != $id) {
            $this->close();
            $this->newDoc = false;

            if (! $find = $this->findUser($id)) {
                $this->id = null;
            } else {
                $this->set('editedon', time());
                $result = $this->query("
                    SELECT * from {$this->makeTable('user_attributes')} as attribute
                    LEFT JOIN {$this->makeTable('manager_users')} as user ON user.id=attribute.internalKey
                    WHERE {$find}='{$this->escape($id)}'
                ");
                $this->field = $this->modx->db->getRow($result);

                $this->id = empty($this->field['internalKey']) ? null : $this->get('internalKey');
                $this->store($this->toArray());
                $result = $this->query("SELECT * FROM {$this->makeTable('user_roles')} WHERE `id`={$this->get('role')}");
                $permissions = $this->modx->db->getRow($result);
                unset($permissions['id'], $permissions['name'], $permissions['description']);
                $this->mgrPermissions = $permissions;
                unset($this->field['id']);
                unset($this->field['internalKey']);
            }
        }

        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        if (is_scalar($value) && is_scalar($key) && ! empty($key)) {
            switch ($key) {
                case 'password':
                    $this->givenPassword = $value;
                    $value = $this->getPassword($value);
                    break;
                case 'sessionid':
                    session_regenerate_id(false);
                    $value = session_id();
                    if ($mid = $this->modx->getLoginUserID('mgr')) {
                        $this->modx->db->query("UPDATE {$this->makeTable('active_user_locks')} SET `sid`='{$value}' WHERE `internalKey`={$mid}");
                        $this->modx->db->query("UPDATE {$this->makeTable('active_user_sessions')} SET `sid`='{$value}' WHERE `internalKey`={$mid}");
                        $this->modx->db->query("UPDATE {$this->makeTable('active_users')} SET `sid`='{$value}' WHERE `internalKey`={$mid}");
                    }
                    break;
                case 'editedon':
                case 'createdon':
                    $value = $this->getTime($value);
                    break;
            }
            $this->field[$key] = $value;
        }

        return $this;
    }

    /**
     * @param $pass
     * @return string
     */
    public function getPassword($pass)
    {
        return $this->modx->phpass->HashPassword($pass);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasPermission($name)
    {
        return (is_string($name) && $name && isset($this->mgrPermissions[$name]));
    }

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool|int|null|void
     */
    public function save($fire_events = false, $clearCache = false)
    {
        if ($this->get('email') == '' || $this->get('username') == '' || $this->get('password') == '') {
            $this->log['EmptyPKField'] = 'Email, username or password is empty <pre>' . print_r($this->toArray(),
                    true) . '</pre>';

            return false;
        }

        if (! $this->checkUnique('manager_users', 'username')) {
            $this->log['UniqueUsername'] = 'username not unique <pre>' . print_r($this->get('username'),
                    true) . '</pre>';

            return false;
        }

        if (! $this->checkUnique('user_attributes', 'email', 'internalKey')) {
            $this->log['UniqueEmail'] = 'Email not unique <pre>' . print_r($this->get('email'), true) . '</pre>';

            return false;
        }

        if(! $this->get('role')) {
            $this->log['UniqueEmail'] = 'Wrong manager role <pre>' . print_r($this->get('role'), true) . '</pre>';
        }

        $this->set('sessionid', '');
        $fld = $this->toArray();
        foreach ($this->default_field['user'] as $key => $value) {
            $tmp = $this->get($key);
            if ($this->newDoc && (!is_int($tmp) && $tmp == '')) {
                $this->field[$key] = $value;
            }
            $this->Uset($key, 'user');
            unset($fld[$key]);
        }
        if (! empty($this->set['user'])) {
            if ($this->newDoc) {
                $SQL = "INSERT into {$this->makeTable('manager_users')} SET " . implode(', ', $this->set['user']);
            } else {
                $SQL = "UPDATE {$this->makeTable('manager_users')} SET " . implode(', ',
                        $this->set['user']) . " WHERE id = " . $this->id;
            }
            $this->query($SQL);
        }

        if ($this->newDoc) {
            $this->id = $this->modx->db->getInsertId();
        }

        foreach ($this->default_field['attribute'] as $key => $value) {
            $tmp = $this->get($key);
            if ($this->newDoc && (!is_int($tmp) && $tmp == '')) {
                $this->field[$key] = $value;
            }
            $this->Uset($key, 'attribute');
            unset($fld[$key]);
        }
        if (! empty($this->set['attribute'])) {
            if ($this->newDoc) {
                $this->set('internalKey', $this->id)->Uset('internalKey', 'attribute');
                $SQL = "INSERT into {$this->makeTable('user_attributes')} SET " . implode(', ',
                        $this->set['attribute']);
            } else {
                $SQL = "UPDATE {$this->makeTable('user_attributes')} SET " . implode(', ',
                        $this->set['attribute']) . " WHERE  internalKey = " . $this->getID();
            }
            $this->query($SQL);
        }
        unset($fld['id']);
        foreach ($fld as $key => $value) {
            if ($value == '' || ! $this->isChanged($key)) {
                continue;
            }
            $result = $this->query("SELECT `setting_value` FROM {$this->makeTable('user_settings')} WHERE `user` = '{$this->id}' AND `setting_name` = '{$key}'");
            if ($this->modx->db->getRecordCount($result) > 0) {
                $this->query("UPDATE {$this->makeTable('user_settings')} SET `setting_value` = '{$value}' WHERE `user` = '{$this->id}' AND `setting_name` = '{$key}';");
            } else {
                $this->query("INSERT into {$this->makeTable('user_settings')} SET `user` = {$this->id},`setting_name` = '{$key}',`setting_value` = '{$value}';");
            }
        }
        // TODO
        if (! $this->newDoc && $this->givenPassword) {
            $this->invokeEvent('OnManagerChangePassword', array(
                'userObj'      => $this,
                'userid'       => $this->id,
                'user'         => $this->toArray(),
                'userpassword' => $this->givenPassword,
                'username'     => $this->get('username')
            ), $fire_events);
        }

        if (! empty($this->groupIds)) {
            $this->setUserGroups($this->id, $this->groupIds);
        }
        // TODO
        $this->invokeEvent('OnManagerSaveUser', array(
            'userObj'      => $this,
            'mode'         => $this->newDoc ? "new" : "upd",
            'user'         => $this->toArray(),
            "userid"       => $this->getID(),
            "username"     => $this->get('username'),
            "userpassword" => $this->givenPassword,
            "useremail"    => $this->get('email'),
            "userfullname" => $this->get('fullname'),
            "userroleid"   => $this->get('role')
        ), $fire_events);

        if ($clearCache) {
            $this->clearCache($fire_events);
        }

        return $this->id;
    }

    /**
     * @param string|int $ids
     * @param bool $fire_events
     * @return bool
     */
    public function delete($ids, $fire_events = false)
    {
        $flag = false;
        if ($this->edit($ids)) {
            $q = $this->query("
          DELETE user,attribute FROM {$this->makeTable('user_attributes')} as attribute
            LEFT JOIN {$this->makeTable('manager_users')} as user ON user.id=attribute.internalKey
            WHERE attribute.internalKey='{$this->escape($this->getID())}'");
            if ($this->modx->db->getAffectedRows($q)) {
                $flag = true;
                $this->query("DELETE FROM {$this->makeTable('user_settings')} WHERE user='{$this->getID()}'");
                $this->query("DELETE FROM {$this->makeTable('member_groups')} WHERE member='{$this->getID()}'");
                $this->invokeEvent('OnManagerDeleteUser', array(
                    'userObj'     => $this,
                    'userid'      => $this->getID(),
                    'internalKey' => $this->getID(),
                    'username'    => $this->get('username'),
                    'timestamp'   => time()
                ), $fire_events);
            }
        }
        $this->close();

        return $flag;
    }

    /**
     * @param int $id
     * @param bool|integer $fulltime
     * @param string $cookieName
     * @param bool $fire_events
     * @return bool
     */
    public function authUser($id = 0, $fulltime = true, $cookieName = 'modx_remember_manager', $fire_events = false)
    {
        $flag = false;
        if (null === $this->getID() && $id) {
            $this->edit($id);
        }
        if (null !== $this->getID()) {
            $flag = true;
            $this->save(false);
            $this->SessionHandler('start', $cookieName, $fulltime);
            $this->invokeEvent("OnManagerLogin", array(
                'userObj'      => $this,
                'userid'       => $this->getID(),
                'username'     => $this->get('username'),
                'userpassword' => $this->givenPassword,
                'rememberme'   => $fulltime
            ), $fire_events);
        }

        return $flag;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function checkBlock($id = 0)
    {
        $tmp = clone $this;
        if ($id && $tmp->getID() != $id) {
            $tmp->edit($id);
        }
        $now = time();

        $b = $tmp->get('blocked');
        $bu = $tmp->get('blockeduntil');
        $ba = $tmp->get('blockedafter');
        $flag = (($b && ! $bu && ! $ba) || ($bu && $now < $bu) || ($ba && $now > $ba));
        unset($tmp);

        return $flag;
    }

    /**
     * @param $id
     * @param $password
     * @param $blocker
     * @param bool $fire_events
     * @return bool
     */
    public function testAuth($id, $password, $blocker, $fire_events = false)
    {
        $tmp = clone $this;
        if ($id && $tmp->getID() != $id) {
            $tmp->edit($id);
        }

        $flag = $pluginFlag = false;
        if (
            (null !== $tmp->getID()) && (! $blocker || ($blocker && ! $tmp->checkBlock($id)))
        ) {
            $_password = $tmp->get('password');
            $eventResult = $this->getInvokeEventResult('OnManagerAuthentication', array(
                'userObj'       => $this,
                'userid'        => $tmp->getID(),
                'username'      => $tmp->get('username'),
                'userpassword'  => $password,
                'savedpassword' => $_password
            ), $fire_events);
            if (is_array($eventResult)) {
                foreach ($eventResult as $result) {
                    $pluginFlag = (bool)$result;
                }
            } else {
                $pluginFlag = (bool)$eventResult;
            }
            if (! $pluginFlag) {
                $hashType = $this->getPasswordHashType($_password);
                switch ($hashType) {
                    case 'phpass':
                        $flag = $this->modx->phpass->CheckPassword($password, $_password);
                        break;
                    case 'md5':
                        $flag = $_password == md5($password);
                        break;
                    case 'v1':
                        $algorithm = \APIhelpers::getkey($this->modx->config, 'pwd_hash_algo', 'UNCRYPT');
                        $userAlgorithm = $this->getPasswordHashAlgorithm($_password);
                        if ($algorithm !== $userAlgorithm) {
                            $algorithm = $userAlgorithm;
                        }
                        $flag = $_password == $this->makeHash($password, $tmp->getID(), $algorithm);
                        break;
                }
                if ($flag && $hashType == 'md5' || $hashType == 'v1') {
                    $tmp->set('password', $password)->save();
                    if ($id == $this->getID()) {
                        $this->field['password'] = $tmp->get('password');
                    }
                }
            }
        }
        unset($tmp);

        return $flag || $pluginFlag;
    }

    /**
     * @param string $cookieName
     * @param bool $fire_events
     */
    public function logOut($cookieName = 'modx_remember_manager', $fire_events = false)
    {
        if (! $uid = $this->modx->getLoginUserID('mgr')) {
            return;
        }
        $params = array(
            'username'    => $_SESSION['mgrShortname'],
            'internalKey' => $uid
        );
        $this->invokeEvent('OnBeforeManagerLogout', $params, $fire_events);
        $this->SessionHandler('destroy', $cookieName ? $cookieName : 'modx_remember_manager');
        $this->modx->db->delete($this->modx->getFullTableName('active_user_locks'), "sid = '{$this->modx->sid}'");
        // Clean up active_user_sessions
        $this->modx->db->delete($this->modx->getFullTableName('active_user_sessions'), "sid = '{$this->modx->sid}'");
        $this->invokeEvent('OnManagerLogout', $params, $fire_events);
    }

    /**
     * SessionHandler
     * Starts the user session on login success. Destroys session on error or logout.
     *
     * @param string $directive ('start' or 'destroy')
     * @param string $cookieName
     * @param bool|integer $remember
     * @return modUsers
     * @author Raymond Irving
     * @author Scotty Delicious
     *
     * remeber может быть числом в секундах
     */
    protected function SessionHandler($directive, $cookieName, $remember = true)
    {
        switch ($directive) {
            case 'start':
                if ($this->getID() !== null) {
                    $_SESSION['usertype'] = 'manager';
                    $_SESSION['mgrShortname'] = $this->get('username');
                    $_SESSION['mgrFullname'] = $this->get('fullname');
                    $_SESSION['mgrEmail'] = $this->get('email');
                    $_SESSION['mgrValidated'] = 1;
                    $_SESSION['mgrInternalKey'] = $this->getID();
                    $_SESSION['mgrFailedlogins'] = $this->get('failedlogincount');
                    $_SESSION['mgrLastlogin'] = $this->get('lastlogin');
                    $_SESSION['mgrLogincount'] = $this->get('logincount');
                    $_SESSION['mgrRole'] = $this->get('role');
                    $_SESSION['mgrPermissions'] = $this->mgrPermissions;
                    $_SESSION['mgrDocgroups'] = $this->getDocumentGroups();
                    $_SESSION['mgrToken'] = md5($this->get('sessionid'));
                    if (! empty($remember)) {
                        $this->setAutoLoginCookie($cookieName, $remember);
                    }
                }
                break;
            case 'destroy':
                if (isset($_SESSION['mgrValidated'])) {
                    unset($_SESSION['usertype']);
                    unset($_SESSION['mgrShortname']);
                    unset($_SESSION['mgrFullname']);
                    unset($_SESSION['mgrEmail']);
                    unset($_SESSION['mgrValidated']);
                    unset($_SESSION['mgrInternalKey']);
                    unset($_SESSION['mgrFailedlogins']);
                    unset($_SESSION['mgrLastlogin']);
                    unset($_SESSION['mgrLogincount']);
                    unset($_SESSION['mgrDocgroups']);
                    unset($_SESSION['mgrPermissions']);
                    unset($_SESSION['mgrToken']);
                    setcookie($cookieName, '', time() - 60, MODX_BASE_URL);
                } else {
                    if (isset($_COOKIE[session_name()])) {
                        setcookie(session_name(), '', time() - 60, MODX_BASE_URL);
                    }
                    setcookie($cookieName, '', time() - 60, MODX_BASE_URL);
                    session_destroy();
                }
                break;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        $out = $this->modxConfig('server_protocol') == 'http' ? false : true;

        return $out;
    }

    /**
     * @param $cookieName
     * @param bool|integer $remember
     * @return $this
     */
    public function setAutoLoginCookie($cookieName, $remember = true)
    {
        if (! empty($cookieName) && $this->getID() !== null) {
            $secure = $this->isSecure();
            $remember = is_bool($remember) ? $this->getRememberTime() : (int)$remember;
            $cookieValue = $this->get('username');
            $cookieExpires = time() + $remember;
            setcookie($cookieName, $cookieValue, $cookieExpires, MODX_BASE_URL, '', $secure, true);
        }

        return $this;
    }

    /**
     * @param int $userID
     * @return array
     */
    public function getDocumentGroups($userID = 0)
    {
        $out = array();
        $user = $this->switchObject($userID);
        if (null !== $user->getID()) {
            $member_groups = $this->modx->getFullTableName('member_groups');
            $membergroup_access = $this->modx->getFullTableName('membergroup_access');

            $sql = "SELECT `uga`.`documentgroup` FROM {$member_groups} as `ug`
                INNER JOIN {$membergroup_access} as `uga` ON `uga`.`membergroup`=`ug`.`user_group` WHERE `ug`.`member` = " . $user->getID();
            $out = $this->modx->db->getColumn('documentgroup', $this->query($sql));

        }
        unset($user);

        return $out;
    }

    /**
     * @param int $userID
     * @return array
     */
    public function getUserGroups($userID = 0)
    {
        $out = array();
        $user = $this->switchObject($userID);
        if (null !== $user->getID()) {
            $member_groups = $this->makeTable('member_groups');
            $membergroup_names = $this->makeTable('membergroup_names');

            $rs = $this->query("SELECT `ug`.`user_group`, `ugn`.`name` FROM {$member_groups} as `ug`
                INNER JOIN {$membergroup_names} as `ugn` ON `ugn`.`id`=`ug`.`user_group`
                WHERE `ug`.`member` = " . $user->getID());
            while ($row = $this->modx->db->getRow($rs)) {
                $out[$row['user_group']] = $row['name'];
            }
        }
        unset($user);

        return $out;
    }

    /**
     * @param int $userID
     * @param array $groupIds
     * @return $this
     */
    public function setUserGroups($userID = 0, $groupIds = array())
    {
        if (!is_array($groupIds)) {
            return $this;
        }
        if ($this->newDoc && $userID == 0) {
            $this->groupIds = $groupIds;
        } else {
            $user = $this->switchObject($userID);
            if ($uid = $user->getID()) {
                foreach ($groupIds as $gid) {
                    $this->query("REPLACE INTO {$this->makeTable('member_groups')} (`user_group`, `member`) VALUES ('{$gid}', '{$uid}')");
                }
                if (! $this->newDoc) {
                    $groupIds = empty($groupIds) ? '0' : implode(',', $groupIds);
                    $this->query("DELETE FROM {$this->makeTable('member_groups')} WHERE `member`={$uid} AND `user_group` NOT IN ({$groupIds})");
                }
            }
            unset($user);
            $this->groupIds = array();
        }

        return $this;
    }

    /**
     * @param string $pass
     * @return string
     */
    public function getPasswordHashType($pass)
    {
        $out = 'unknown';
        if (substr($pass, 0, 1) === '$') {
            $out = 'phpass';
        } elseif (strpos($pass, '>') !== false) {
            $out = 'v1';
        } elseif (strlen($pass) === 32) {
            $out = 'md5';
        }

        return $out;
    }

    /**
     * @param string $pass
     * @return string
     */
    public function getPasswordHashAlgorithm($pass)
    {
        $pointer = strpos($pass, '>');
        $out = $pointer === false ? 'NOSALT' : substr($pass, 0, $pointer);

        return strtoupper($out);
    }

    /**
     * @param string $pass
     * @param int $seed
     * @param string $algorithm
     * @return string
     */
    public function makeHash($pass, $seed, $algorithm)
    {
        $salt = md5($pass . $seed);

        switch ($algorithm) {
            case 'BLOWFISH_Y':
                $salt = '$2y$07$' . substr($salt, 0, 22);
                break;
            case 'BLOWFISH_A':
                $salt = '$2a$07$' . substr($salt, 0, 22);
                break;
            case 'SHA512':
                $salt = '$6$' . substr($salt, 0, 16);
                break;
            case 'SHA256':
                $salt = '$5$' . substr($salt, 0, 16);
                break;
            case 'MD5':
                $salt = '$1$' . substr($salt, 0, 8);
                break;
            default:
                $algorithm = 'UNCRYPT';
                break;
        }

        $pass = $algorithm !== 'UNCRYPT' ? sha1($pass) . crypt($pass, $salt) : sha1($salt . $pass);
        $out = strtolower($algorithm) . '>' . md5($salt . $pass) . substr(md5($salt), 0, 8);

        return $out;
    }


}
