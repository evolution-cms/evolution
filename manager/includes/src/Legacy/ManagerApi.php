<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Interfaces\ManagerApiInterface;
/*
 * MODX Manager API Class
 * Written by Raymond Irving 2005
 *
 */

//global $_PAGE; // page view state object. Usage $_PAGE['vs']['propertyname'] = $value;

// Content manager wrapper class
class ManagerApi implements ManagerApiInterface
{
    /**
     * @var string
     */
    public $action; // action directive

    /**
     * ManagerAPI constructor.
     */
    public function __construct()
    {
        global $action;
        $this->action = $action; // set action directive
    }

    /**
     * @param int $id
     */
    public function initPageViewState($id = 0)
    {
        global $_PAGE;
        $vsid = isset($_SESSION["mgrPageViewSID"]) ? $_SESSION["mgrPageViewSID"] : '';
        if ($vsid != $this->action) {
            $_SESSION["mgrPageViewSDATA"] = array(); // new view state
            $_SESSION["mgrPageViewSID"] = $id > 0 ? $id : $this->action; // set id
        }
        $_PAGE['vs'] = &$_SESSION["mgrPageViewSDATA"]; // restore viewstate
    }

    /**
     * save page view state - not really necessary,
     *
     * @param int $id
     */
    public function savePageViewState($id = 0)
    {
        global $_PAGE;
        $_SESSION["mgrPageViewSDATA"] = $_PAGE['vs'];
        $_SESSION["mgrPageViewSID"] = $id > 0 ? $id : $this->action;
    }

    /**
     * check for saved form
     *
     * @return bool
     */
    public function hasFormValues()
    {
        if (isset($_SESSION["mgrFormValueId"])) {
            if ($this->action == $_SESSION["mgrFormValueId"]) {
                return true;
            } else {
                $this->clearSavedFormValues();
            }
        }

        return false;
    }

    /**
     * saved form post from $_POST
     *
     * @param int $id
     */
    public function saveFormValues($id = 0)
    {
        $_SESSION["mgrFormValues"] = $_POST;
        $_SESSION["mgrFormValueId"] = $id > 0 ? $id : $this->action;
    }

    /**
     * load saved form values into $_POST
     *
     * @return bool
     */
    public function loadFormValues()
    {

        if (!$this->hasFormValues()) {
            return false;
        }

        $p = $_SESSION["mgrFormValues"];
        $this->clearSavedFormValues();
        foreach ($p as $k => $v) {
            $_POST[$k] = $v;
        }

        return true;
    }

    /**
     * clear form post
     *
     * @return void
     */
    public function clearSavedFormValues()
    {
        unset($_SESSION["mgrFormValues"]);
        unset($_SESSION["mgrFormValueId"]);
    }

    /**
     * @param string $db_value
     * @return string
     */
    public function getHashType($db_value = '')
    { // md5 | v1 | phpass
        $c = substr($db_value, 0, 1);
        if ($c === '$') {
            return 'phpass';
        } elseif (strlen($db_value) === 32) {
            return 'md5';
        } elseif ($c !== '$' && strpos($db_value, '>') !== false) {
            return 'v1';
        } else {
            return 'unknown';
        }
    }

    /**
     * @param string $password
     * @param string $seed
     * @return string
     */
    public function genV1Hash($password, $seed = '1')
    { // $seed is user_id basically
        $modx = evolutionCMS();

        if (isset($modx->config['pwd_hash_algo']) && !empty($modx->config['pwd_hash_algo'])) {
            $algorithm = $modx->config['pwd_hash_algo'];
        } else {
            $algorithm = 'UNCRYPT';
        }

        $salt = md5($password . $seed);

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
        }

        if ($algorithm !== 'UNCRYPT') {
            $password = sha1($password) . crypt($password, $salt);
        } else {
            $password = sha1($salt . $password);
        }

        $result = strtolower($algorithm) . '>' . md5($salt . $password) . substr(md5($salt), 0, 8);

        return $result;
    }

    /**
     * @param string $uid
     * @return string
     */
    public function getV1UserHashAlgorithm($uid)
    {
        $modx = evolutionCMS();
        $tbl_manager_users = $modx->getDatabase()->getFullTableName('manager_users');
        $uid = $modx->getDatabase()->escape($uid);
        $rs = $modx->getDatabase()->select('password', $tbl_manager_users, "id='{$uid}'");
        $password = $modx->getDatabase()->getValue($rs);

        if (strpos($password, '>') === false) {
            $algo = 'NOSALT';
        } else {
            $algo = substr($password, 0, strpos($password, '>'));
        }

        return strtoupper($algo);
    }

    /**
     * @param string $algorithm
     * @return bool
     */
    public function checkHashAlgorithm($algorithm = '')
    {
        $result = false;
        if (!empty($algorithm)) {
            switch ($algorithm) {
                case 'BLOWFISH_Y':
                    if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1) {
                        if (version_compare('5.3.7', PHP_VERSION) <= 0) {
                            $result = true;
                        }
                    }
                    break;
                case 'BLOWFISH_A':
                    if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1) {
                        $result = true;
                    }
                    break;
                case 'SHA512':
                    if (defined('CRYPT_SHA512') && CRYPT_SHA512 == 1) {
                        $result = true;
                    }
                    break;
                case 'SHA256':
                    if (defined('CRYPT_SHA256') && CRYPT_SHA256 == 1) {
                        $result = true;
                    }
                    break;
                case 'MD5':
                    if (defined('CRYPT_MD5') && CRYPT_MD5 == 1 && PHP_VERSION != '5.3.7') {
                        $result = true;
                    }
                    break;
                case 'UNCRYPT':
                    $result = true;
                    break;
            }
        }

        return $result;
    }

    /**
     * @param string $check_files
     * @return string
     */
    public function getSystemChecksum($check_files)
    {
        $_ = array();
        $check_files = trim($check_files);
        $check_files = explode("\n", $check_files);
        foreach ($check_files as $file) {
            $file = trim($file);
            $file = MODX_BASE_PATH . $file;
            if (!is_file($file)) {
                continue;
            }
            $_[$file] = md5_file($file);
        }

        return serialize($_);
    }

    /**
     * @param string $check_files
     * @param string $checksum
     * @return array
     */
    public function getModifiedSystemFilesList($check_files, $checksum)
    {
        $_ = array();
        $check_files = trim($check_files);
        $check_files = explode("\n", $check_files);
        $checksum = unserialize($checksum);
        foreach ($check_files as $file) {
            $file = trim($file);
            $filePath = MODX_BASE_PATH . $file;
            if (!is_file($filePath)) {
                continue;
            }
            if (md5_file($filePath) != $checksum[$filePath]) {
                $_[] = $file;
            }
        }

        return $_;
    }

    /**
     * @param string $checksum
     */
    public function setSystemChecksum($checksum)
    {
        $modx = evolutionCMS();
        $tbl_system_settings = $modx->getDatabase()->getFullTableName('system_settings');
        $sql = "REPLACE INTO {$tbl_system_settings} (setting_name, setting_value) VALUES ('sys_files_checksum','" . $modx->getDatabase()->escape($checksum) . "')";
        $modx->getDatabase()->query($sql);
    }

    /**
     * @return array|string
     */
    public function checkSystemChecksum()
    {
        $modx = evolutionCMS();

        if (!isset($modx->config['check_files_onlogin']) || empty($modx->config['check_files_onlogin'])) {
            return '0';
        }

        $current = $this->getSystemChecksum($modx->config['check_files_onlogin']);
        if (empty($current)) {
            return '0';
        }

        if (!isset($modx->config['sys_files_checksum']) || empty($modx->config['sys_files_checksum'])) {
            $this->setSystemChecksum($current);

            return '0';
        }
        if ($current === $modx->config['sys_files_checksum']) {
            $result = '0';
        } else {
            $result = $this->getModifiedSystemFilesList($modx->config['check_files_onlogin'],
                $modx->config['sys_files_checksum']);
        }

        return $result;
    }

    /**
     * @param bool|string $key
     * @return null|string|array
     */
    public function getLastUserSetting($key = false)
    {
        $modx = evolutionCMS();

        $rs = $modx->getDatabase()->select('*', $modx->getDatabase()->getFullTableName('user_settings'),
            "user = '{$_SESSION['mgrInternalKey']}'");

        $usersettings = array();
        while ($row = $modx->getDatabase()->getRow($rs)) {
            if (substr($row['setting_name'], 0, 6) == '_LAST_') {
                $name = substr($row['setting_name'], 6);
                $usersettings[$name] = $row['setting_value'];
            }
        }

        if ($key === false) {
            return $usersettings;
        } else {
            return isset($usersettings[$key]) ? $usersettings[$key] : null;
        }
    }

    /**
     * @param array $settings
     * @param string $val
     */
    public function saveLastUserSetting($settings, $val = '')
    {
        $modx = evolutionCMS();

        if (!empty($settings)) {
            if (!is_array($settings)) {
                $settings = array($settings => $val);
            }

            foreach ($settings as $key => $val) {
                $f = array();
                $f['user'] = $_SESSION['mgrInternalKey'];
                $f['setting_name'] = '_LAST_' . $key;
                $f['setting_value'] = $val;
                $f = $modx->getDatabase()->escape($f);
                $f = "(`" . implode("`, `", array_keys($f)) . "`) VALUES('" . implode("', '", array_values($f)) . "')";
                $f .= " ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $modx->getDatabase()->insert($f, $modx->getDatabase()->getFullTableName('user_settings'));
            }
        }
    }

    /**
     * @param $path
     * @return string
     */
    public function loadDatePicker($path)
    {
        $modx = evolutionCMS();
        include_once($path);
        $dp = new \DATEPICKER();

        return $modx->mergeSettingsContent($dp->getDP());
    }
}
