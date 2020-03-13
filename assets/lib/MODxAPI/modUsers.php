<?php
require_once('MODx.php');

/**
 * Class modUsers
 */
class modUsers extends MODxAPI
{
    /**
     * @var array
     */
    protected $default_field = array(
        'user'      => array(
            'username' => '',
            'password' => '',
            'cachepwd' => ''
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
            'editedon'         => 0,
            'verified'         => 0
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
    protected $userIdCache = array(
        'attribute.internalKey' => '',
        'attribute.email' => '',
        'user.username' => ''
    );

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
    }

    /**
     * @param $val
     * @return $this
     */
    protected function setRememberTime($val)
    {
        $this->rememberTime = (int)$val;
        return $this;
    }

    /**
     * @return integer
     */
    public function getRememberTime()
    {
        return $this->rememberTime;
    }

    /**
     * @param $key
     * @return bool
     */
    public function issetField($key)
    {
        return (array_key_exists($key, $this->default_field['user']) || array_key_exists(
            $key,
            $this->default_field['attribute']
        ) || in_array($key, $this->default_field['hidden']));
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
     *
     */
    public function close()
    {
        parent::close();
        $this->userIdCache = array(
            'attribute.internalKey' => '',
            'attribute.email' => '',
            'user.username' => ''
        );
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function getUserId($id) {
        $find = $this->findUser($id);
        if ($find && ! empty($this->userIdCache[$find])) {
            $id = $this->userIdCache[$find];
        } else {
            $id = null;
        }

        return $id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function edit($id)
    {
        $id = is_scalar($id) ? trim($id) : '';
        if ($this->getUserId($id) != $id) {
            $this->close();
            $this->newDoc = false;

            if (! $find = $this->findUser($id)) {
                $this->id = null;
            } else {
                $this->set('editedon', time());
                $this->editQuery($find, $id);
                $this->id = empty($this->field['internalKey']) ? null : $this->get('internalKey');
                $this->userIdCache['attribute.internalKey'] = $this->getID();
                $this->userIdCache['attribute.email'] = $this->get('email');
                $this->userIdCache['user.username'] = $this->get('username');
                $this->loadUserSettings();
                $this->store($this->toArray());
                unset($this->field['id']);
                unset($this->field['internalKey']);
            }
        }

        return $this;
    }

    protected function loadUserSettings()
    {
        $webUser = $this->getID();

        if (!empty($webUser)) {
            $settings = $this->modx->db->makeArray($this->modx->db->select('*', $this->makeTable('web_user_settings'), "webuser = {$webUser}"));
            $this->fromArray(array_column($settings, 'setting_value', 'setting_name'));
        }
    }

    /**
     * @param string $find
     * @param string $id
     */
    protected function editQuery($find, $id)
    {
        $result = $this->query("
            SELECT * from {$this->makeTable('web_user_attributes')} as attribute
            LEFT JOIN {$this->makeTable('web_users')} as user ON user.id=attribute.internalKey
            WHERE {$find}='{$this->escape($id)}'
        ");
        $this->field = $this->modx->db->getRow($result);
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
                    //short bug fix when authoring a web user if the manager is logged in
                    $oldSessionId  =  session_id();
                    session_regenerate_id(false);
                    $value = session_id();
                    if ($mid = $this->modx->getLoginUserID('mgr')) {
                        //short bug fix when authoring a web user if the manager is logged in
                        $this->modx->db->delete($this->makeTable('active_users'),"`internalKey`={$mid} and `sid` != '{$oldSessionId}'  " );
                        $this->modx->db->delete($this->makeTable('active_user_sessions'),"`internalKey`={$mid} and `sid` != '{$oldSessionId}'  " );

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
        return md5($pass);
    }

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool|int|null|void
     */
    public function save($fire_events = false, $clearCache = false)
    {
        if ($this->get('email') == '' || $this->get('username') == '' || $this->get('password') == '') {
            $this->log['EmptyPKField'] = 'Email, username or password is empty <pre>' . print_r(
                $this->toArray(),
                true
            ) . '</pre>';

            return false;
        }

        if ($this->isChanged('username') && ! $this->checkUnique('web_users', 'username')) {
            $this->log['UniqueUsername'] = 'username not unique <pre>' . print_r(
                $this->get('username'),
                true
            ) . '</pre>';

            return false;
        }

        if ($this->isChanged('username') && ! $this->checkUnique('web_user_attributes', 'email', 'internalKey')) {
            $this->log['UniqueEmail'] = 'Email not unique <pre>' . print_r($this->get('email'), true) . '</pre>';

            return false;
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
                $SQL = "INSERT into {$this->makeTable('web_users')} SET " . implode(', ', $this->set['user']);
            } else {
                $SQL = "UPDATE {$this->makeTable('web_users')} SET " . implode(
                    ', ',
                    $this->set['user']
                ) . " WHERE id = " . $this->id;
            }
            $this->query($SQL);
        }

        if ($this->newDoc) {
            $this->id = $this->modx->db->getInsertId();
        }

        $this->saveQuery($fld);
        unset($fld['id']);

        if (! $this->newDoc && $this->givenPassword) {
            $this->invokeEvent('OnWebChangePassword', array(
                'userObj'      => $this,
                'userid'       => $this->id,
                'user'         => $this->toArray(),
                'userpassword' => $this->givenPassword,
                'internalKey'  => $this->id,
                'username'     => $this->get('username')
            ), $fire_events);
        }

        if (! empty($this->groupIds)) {
            $this->setUserGroups($this->id, $this->groupIds);
        }

        $this->invokeEvent('OnWebSaveUser', array(
            'userObj' => $this,
            'mode'    => $this->newDoc ? "new" : "upd",
            'id'      => $this->id,
            'user'    => $this->toArray()
        ), $fire_events);

        if ($clearCache) {
            $this->clearCache($fire_events);
        }

        return $this->id;
    }

    /**
     * @param  array  $fld
     */
    protected function saveQuery(array &$fld)
    {
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
                $SQL = "INSERT into {$this->makeTable('web_user_attributes')} SET " . implode(
                    ', ',
                    $this->set['attribute']
                );
            } else {
                $SQL = "UPDATE {$this->makeTable('web_user_attributes')} SET " . implode(
                    ', ',
                    $this->set['attribute']
                ) . " WHERE  internalKey = " . $this->getID();
            }
            $this->query($SQL);
        }
    }

    /**
     * @param $ids
     * @param bool $fire_events
     * @return bool|null|void
     */
    public function delete($ids, $fire_events = false)
    {
        if ($this->edit($ids)) {
            $flag = $this->deleteQuery();
            $this->query("DELETE FROM {$this->makeTable('web_user_settings')} WHERE webuser='{$this->getID()}'");
            $this->query("DELETE FROM {$this->makeTable('web_groups')} WHERE webuser='{$this->getID()}'");
            $this->invokeEvent('OnWebDeleteUser', array(
                'userObj'     => $this,
                'userid'      => $this->getID(),
                'internalKey' => $this->getID(),
                'username'    => $this->get('username'),
                'timestamp'   => time()
            ), $fire_events);
        } else {
            $flag = false;
        }
        $this->close();

        return $flag;
    }

    /**
     * @return mixed
     */
    protected function deleteQuery()
    {
        return $this->query("
          DELETE user,attribute FROM {$this->makeTable('web_user_attributes')} as attribute
            LEFT JOIN {$this->makeTable('web_users')} as user ON user.id=attribute.internalKey
            WHERE attribute.internalKey='{$this->escape($this->getID())}'");
    }

    /**
     * @param int $id
     * @param bool|integer $fulltime
     * @param string $cookieName
     * @param bool $fire_events
     * @return bool
     */
    public function authUser($id = 0, $fulltime = true, $cookieName = 'WebLoginPE', $fire_events = false)
    {
        $flag = false;
        if (null === $this->getID() && $id) {
            $this->edit($id);
        }
        if (null !== $this->getID()) {
            $flag = true;
            $this->save(false);
            $this->SessionHandler('start', $cookieName, $fulltime);
            $this->invokeEvent("OnWebLogin", array(
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
        if ($this->getID()) {
            $tmp = clone $this;
        } else {
            $tmp = $this;
        }
        if ($id && $tmp->getUserId($id) != $id) {
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
        if ($this->getID()) {
            $tmp = clone $this;
        } else {
            $tmp = $this;
        }
        if ($id && $tmp->getUserId($id) != $id) {
            $tmp->edit($id);
        }

        $flag = $pluginFlag = false;
        if ((null !== $tmp->getID()) && (! $blocker || ($blocker && ! $tmp->checkBlock($id)))
        ) {
            $eventResult = $this->getInvokeEventResult('OnWebAuthentication', array(
                'userObj'       => $this,
                'userid'        => $tmp->getID(),
                'username'      => $tmp->get('username'),
                'userpassword'  => $password,
                'savedpassword' => $tmp->get('password')
            ), $fire_events);
            if (is_array($eventResult)) {
                foreach ($eventResult as $result) {
                    $pluginFlag = (bool)$result;
                }
            } else {
                $pluginFlag = (bool)$eventResult;
            }
            if (! $pluginFlag) {
                $flag = ($tmp->get('password') == $tmp->getPassword($password));
            }
        }
        unset($tmp);

        return $flag || $pluginFlag;
    }

    /**
     * @param bool|integer $fulltime
     * @param string $cookieName
     * @return bool
     */
    public function AutoLogin($fulltime = true, $cookieName = 'WebLoginPE', $fire_events = null)
    {
        $flag = false;
        if (isset($_COOKIE[$cookieName])) {
            $cookie = explode('|', $_COOKIE[$cookieName], 4);
            if (isset($cookie[0], $cookie[1], $cookie[2]) && strlen($cookie[0]) == 32 && strlen($cookie[1]) == 32) {
                if (! $fulltime && isset($cookie[4])) {
                    $fulltime = (int)$cookie[4];
                }
                $this->close();
                $q = $this->modx->db->query("SELECT id FROM " . $this->makeTable('web_users') . " WHERE md5(username)='{$this->escape($cookie[0])}'");
                $id = $this->modx->db->getValue($q);
                if ($this->edit($id)
                    && null !== $this->getID()
                    && $this->get('password') == $cookie[1]
                    && $this->get('sessionid') == $cookie[2]
                    && ! $this->checkBlock($this->getID())
                ) {
                    $flag = $this->authUser($this->getID(), $fulltime, $cookieName, $fire_events);
                }
            }
        }

        return $flag;
    }

    /**
     * @param string $cookieName
     * @param bool $fire_events
     */
    public function logOut($cookieName = 'WebLoginPE', $fire_events = false)
    {
        if (! $uid = $this->modx->getLoginUserID('web')) {
            return;
        }
        $params = array(
            'username'    => $_SESSION['webShortname'],
            'internalKey' => $uid,
            'userid'      => $uid // Bugfix by TS
        );
        $this->invokeEvent('OnBeforeWebLogout', $params, $fire_events);
        $this->SessionHandler('destroy', $cookieName ? $cookieName : 'WebLoginPE');
        $this->invokeEvent('OnWebLogout', $params, $fire_events);
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
                    $_SESSION['webShortname'] = $this->get('username');
                    $_SESSION['webFullname'] = $this->get('fullname');
                    $_SESSION['webEmail'] = $this->get('email');
                    $_SESSION['webValidated'] = 1;
                    $_SESSION['webInternalKey'] = $this->getID();
                    $_SESSION['webValid'] = base64_encode($this->get('password'));
                    $_SESSION['webUser'] = base64_encode($this->get('username'));
                    $_SESSION['webFailedlogins'] = $this->get('failedlogincount');
                    $_SESSION['webLastlogin'] = $this->get('lastlogin');
                    $_SESSION['webnrlogins'] = $this->get('logincount');
                    $_SESSION['webUsrConfigSet'] = array();
                    $_SESSION['webUserGroupNames'] = $this->getUserGroups();
                    $_SESSION['webDocgroups'] = $this->getDocumentGroups();
                    if (! empty($remember)) {
                        $this->setAutoLoginCookie($cookieName, $remember);
                    }
                }
                break;
            case 'destroy':
                if (isset($_SESSION['mgrValidated'])) {
                    unset($_SESSION['webShortname']);
                    unset($_SESSION['webFullname']);
                    unset($_SESSION['webEmail']);
                    unset($_SESSION['webValidated']);
                    unset($_SESSION['webInternalKey']);
                    unset($_SESSION['webValid']);
                    unset($_SESSION['webUser']);
                    unset($_SESSION['webFailedlogins']);
                    unset($_SESSION['webLastlogin']);
                    unset($_SESSION['webnrlogins']);
                    unset($_SESSION['webUsrConfigSet']);
                    unset($_SESSION['webUserGroupNames']);
                    unset($_SESSION['webDocgroups']);

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
            $cookieValue = array(md5($this->get('username')), $this->get('password'), $this->get('sessionid'), $remember);
            $cookieValue = implode('|', $cookieValue);
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
            $web_groups = $this->modx->getFullTableName('web_groups');
            $webgroup_access = $this->modx->getFullTableName('webgroup_access');

            $sql = "SELECT `uga`.`documentgroup` FROM {$web_groups} as `ug`
                INNER JOIN {$webgroup_access} as `uga` ON `uga`.`webgroup`=`ug`.`webgroup`
                WHERE `ug`.`webuser` = " . $user->getID();
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
            $web_groups = $this->makeTable('web_groups');
            $webgroup_names = $this->makeTable('webgroup_names');

            $rs = $this->query("SELECT `ug`.`webgroup`, `ugn`.`name` FROM {$web_groups} as `ug`
                INNER JOIN {$webgroup_names} as `ugn` ON `ugn`.`id`=`ug`.`webgroup`
                WHERE `ug`.`webuser` = " . $user->getID());
            while ($row = $this->modx->db->getRow($rs)) {
                $out[$row['webgroup']] = $row['name'];
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
                    $this->query("REPLACE INTO {$this->makeTable('web_groups')} (`webgroup`, `webuser`) VALUES ('{$gid}', '{$uid}')");
                }
                if (! $this->newDoc) {
                    $groupIds = empty($groupIds) ? '0' : implode(',', $groupIds);
                    $this->query("DELETE FROM {$this->makeTable('web_groups')} WHERE `webuser`={$uid} AND `webgroup` NOT IN ({$groupIds})");
                }
            }
            unset($user);
            $this->groupIds = array();
        }

        return $this;
    }
}
