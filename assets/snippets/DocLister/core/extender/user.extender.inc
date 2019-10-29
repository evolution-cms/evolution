<?php
/**
 * user extender for DocLister
 *
 * @category extender
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 *
 * @param string $userFields fields with user id
 * @param string $usertype get information about managers or web users
 *
 */
class user_DL_Extender extends extDocLister
{
    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var array
     */
    private $userinfo = array();

    /**
     * @var array
     */
    private $table = array();

    /**
     * @return array
     */
    protected function run()
    {
        if ('' != $this->_cfg['fields']) {
            $this->table = $this->getTable();
            $this->_cfg['fields'] = explode(",", $this->_cfg['fields']);
            $userid = array();
            foreach ($this->_cfg['fields'] as $item) {
                $this->fields[$item] = $this->DocLister->getOneField($item, true);
                $userid = array_merge($this->fields[$item], $userid);
            }
            $this->userinfo = $this->getInfo($userid);
            unset($userid);
        }

        return $this->userinfo;
    }

    /**
     * @param $field
     * @param $user
     * @return array|mixed
     */
    public function getUser($field, $user)
    {
        $out = array();
        if (isset($this->fields[$field], $this->userinfo[$user])) {
            $out = $this->userinfo[$user];
        }

        return $out;
    }

    /**
     * @param $arr
     * @return array
     */
    public function setUserData($arr)
    {
        foreach ($arr as $key => $item) {
            $out = $this->DocLister->renameKeyArr($this->getUser($key, $item), 'user', $key);
            if (is_array($out) && count($out) > 0) {
                $arr = array_merge($arr, $out);
            }
        }

        return $arr;
    }

    /**
     * get table name
     *
     * @global string $usertype type users (manager or web user). Default web
     * @return array
     */
    private function getTable()
    {
        $type = $this->DocLister->getCFGDef('usertype', 'web');
        $table = array();
        switch ($type) {
            case 'manager':
            case 'mgr':
                $table['users'] = $this->modx->getFullTableName('manager_users');
                $table['attributes'] = $this->modx->getFullTableName('user_attributes');
                $table['groups'] = $this->modx->getFullTableName('member_groups');
                $table['groupnames'] = $this->modx->getFullTableName('membergroup_names');
                break;
            case 'web':
            default:
                $table['users'] = $this->modx->getFullTableName('web_users');
                $table['attributes'] = $this->modx->getFullTableName('web_user_attributes');
                $table['groups'] = $this->modx->getFullTableName('web_groups');
                $table['groupnames'] = $this->modx->getFullTableName('webgroup_names');
                break;
        }

        return $table;
    }

    /**
     * @param array $user
     * @return array
     */
    private function getInfo($user = array())
    {
        if (!isset($this->table['users'], $this->table['attributes'])) {
            $this->table = $this->getTable();
        }

        $out = array();
        if (count($user) > 0) {
            $user = implode(",", $this->DocLister->cleanIDs($user));
            //without a.sessionid and u.password
            $sql = $this->modx->db->query("
                SELECT
                    u.id,
                    u.username,
                    a.fullname,
                    a.role,
                    a.email,
                    a.phone,
                    a.mobilephone,
                    a.blocked,
                    a.blockeduntil,
                    a.blockedafter,
                    a.logincount,
                    a.lastlogin,
                    a.thislogin,
                    a.failedlogincount,
                    a.dob,
                    a.gender,
                    a.country,
                    a.state,
                    a.city,
                    a.zip,
                    a.fax,
                    a.photo,
                    a.comment
                FROM
                    {$this->table['users']} as u
                LEFT JOIN
                    {$this->table['attributes']} as a
                ON
                    a.internalKey=u.id
                WHERE
                    u.id IN ({$user})
            ");
            while ($tmp = $this->modx->db->getRow($sql)) {
                $out[$tmp['id']] = $tmp;
            }
            if (!empty($out)) {
                $this->getUserGroups($out);
            }
        }

        return $out;
    }

    /**
     * @param array $data
     */
    private function getUserGroups(&$data) {
        $users = array_keys($data);
        if ($users) {
            $type = $this->DocLister->getCFGDef('usertype', 'web');
            $ids = implode(',', $users);
            $table = "{$this->table['groups']} `g` LEFT JOIN {$this->table['groupnames']} `n`";
            switch ($type) {
                case 'manager':
                case 'mgr':
                    $q = "SELECT `member` as `user`, `name` FROM {$table} ON `g`.`user_group` = `n`.`id`  WHERE `member` IN ({$ids})";
                    break;
                case 'web':
                default:
                    $q = "SELECT `webuser` as `user`, `name` FROM {$table} ON `g`.`webgroup` = `n`.`id`  WHERE `webuser` IN ({$ids})";
                    break;
            }
            $q = $this->modx->db->query($q);
            while ($row = $this->modx->db->getRow($q)) {
                $data[$row['user']]['groups'][] = $row['name'];
            }
            foreach($data as &$value) {
                if (isset($value['groups'])) {
                    $value['groups'] = json_encode($value['groups']);
                }
            }
        }
    }

}
