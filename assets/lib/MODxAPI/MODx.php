<?php
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/jsonHelper.class.php');
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLCollection.class.php');

use \Doctrine\Common\Cache\Cache;

/**
 * Class MODxAPIhelpers
 */
class MODxAPIhelpers
{

    /**
     * @param $email
     * @param bool $dns
     * @return false|string
     */
    public function emailValidate($email, $dns = true)
    {
        return \APIhelpers::emailValidate($email, $dns);
    }

    /**
     * @param $len
     * @param string $data
     * @return string
     */
    public function genPass($len, $data = '')
    {
        return \APIhelpers::genPass($len, $data);
    }

    /**
     * @param string $out
     * @return string
     */
    public function getUserIP($out = '127.0.0.1')
    {
        return \APIhelpers::getUserIP($out);
    }

    /**
     * @param $data
     * @return array|mixed|string
     */
    public function sanitarTag($data)
    {
        return \APIhelpers::sanitarTag($data);
    }

    /**
     * @param $value
     * @param int $minLen
     * @param array $alph
     * @param array $mixArray
     * @return bool
     */
    public function checkString($value, $minLen = 1, $alph = array(), $mixArray = array())
    {
        return \APIhelpers::checkString($value, $minLen, $alph, $mixArray);
    }
}

/**
 * Class MODxAPI
 */
abstract class MODxAPI extends MODxAPIhelpers
{
    /**
     * Объект DocumentParser - основной класс MODX
     * @var \DocumentParser
     * @access protected
     */
    protected $modx = null;

    /**
     * @var array
     */
    protected $log = array();

    /**
     * @var array
     */
    protected $field = array();

    /**
     * @var array
     */
    protected $default_field = array();

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var array
     */
    protected $set = array();

    /**
     * @var bool
     */
    protected $newDoc = false;

    /**
     * @var string
     */
    protected $pkName = 'id';

    /**
     * @var string
     */
    protected $ignoreError = '';

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var array
     */
    protected $_query = array();

    /**
     * @var array
     */
    protected $jsonFields = array();

    /**
     * @var array
     */
    protected $store = array();

    /**
     * @var DLCollection
     */
    protected $_decodedFields;

    /**
     * @var array
     */
    private $_table = array();

    private $cache = false;

    /**
     * MODxAPI constructor.
     * @param DocumentParser $modx
     * @param bool $debug
     * @throws Exception
     */
    public function __construct(DocumentParser $modx, $debug = false)
    {
        $this->modx = $modx;
        $this->setDebug($debug);
        $this->_decodedFields = new DLCollection($this->modx);
        $this->cache = isset($this->modx->cache) && $this->modx->cache instanceof Cache;
    }

    /**
     * @param boolean $flag
     * @return $this
     */
    public function setDebug($flag)
    {
        $this->_debug = (bool)$flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return $this->_debug;
    }

    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return $this->default_field;
    }

    /**
     * @param $value
     * @return int|mixed|string
     */
    protected function getTime($value)
    {
        $value = trim($value);
        if (! empty($value)) {
            if (! is_numeric($value)) {
                $value = (int)strtotime($value);
            }
            if (! empty($value)) {
                $value += $this->modxConfig('server_offset_time');
            }
        }

        return $value;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    final public function modxConfig($name, $default = null)
    {
        return APIHelpers::getkey($this->modx->config, $name, $default);
    }

    /**
     * @param $q
     * @return $this
     */
    public function addQuery($q)
    {
        if (is_scalar($q) && ! empty($q)) {
            $this->_query[] = $q;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryList()
    {
        return $this->_query;
    }

    /**
     * @param $SQL
     * @return mixed
     */
    final public function query($SQL)
    {
        if ($this->getDebug()) {
            $this->addQuery($SQL);
        }

        return empty($SQL) ? null : $this->modx->db->query($SQL);
    }

    /**
     * @param $value
     * @return string|void
     */
    final public function escape($value)
    {
        if (! is_scalar($value)) {
            $value = '';
        } else {
            $value = $this->modx->db->escape($value);
        }

        return $value;
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $flag
     * @return $this
     */
    final public function invokeEvent($name, $data = array(), $flag = false)
    {
        if ((bool)$flag === true) {
            $this->modx->invokeEvent($name, $data);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $data
     * @param boolean $flag
     * @return array|bool
     */
    final public function getInvokeEventResult($name, $data = array(), $flag = null)
    {
        $flag = (isset($flag) && $flag !== '') ? (bool)$flag : false;

        return $flag ? $this->modx->invokeEvent($name, $data) : false;
    }

    /**
     * @return $this
     */
    final public function clearLog()
    {
        $this->log = array();

        return $this;
    }

    /**
     * @return array
     */
    final public function getLog()
    {
        return $this->log;
    }

    /**
     * @param bool $flush
     * @return $this
     */
    final public function list_log($flush = false)
    {
        echo '<pre>' . print_r(APIHelpers::sanitarTag($this->log), true) . '</pre>';
        if ($flush) {
            $this->clearLog();
        }

        return $this;
    }

    /**
     * @param bool $full
     * @return string
     */
    final public function getCachePath($full = true)
    {
        $path = $this->modx->getCachePath();
        if ($full) {
            $path = MODX_BASE_PATH . substr($path, strlen(MODX_BASE_URL));
        }

        return $path;
    }

    /**
     * @param boolean $fire_events
     * @param bool $custom
     */
    final public function clearCache($fire_events = false, $custom = false)
    {
        $IDs = array();
        if ($custom === false) {
            $this->modx->clearCache();
            include_once(MODX_MANAGER_PATH . 'processors/cache_sync.class.processor.php');
            $sync = new synccache();
            $path = $this->getCachePath(true);
            $sync->setCachepath($path);
            $sync->setReport(false);
            $sync->emptyCache();
        } else {
            if (is_scalar($custom)) {
                $custom = array($custom);
            }
            switch ($this->modx->config['cache_type']) {
                case 2:
                    $cacheFile = "_*.pageCache.php";
                    break;
                default:
                    $cacheFile = ".pageCache.php";
            }
            if (is_array($custom)) {
                foreach ($custom as $id) {
                    $tmp = glob(MODX_BASE_PATH . "assets/cache/docid_" . $id . $cacheFile);
                    foreach ($tmp as $file) {
                        if (is_readable($file)) {
                            unlink($file);
                        }
                        $IDs[] = $id;
                    }
                }
            }
            clearstatcache();
        }
        $this->invokeEvent('OnSiteRefresh', array('IDs' => $IDs), $fire_events);
    }

    /**
     * @param integer $id
     * @return MODxAPI
     */
    public function switchObject($id)
    {
        switch (true) {
            //Если загружен другой объект - не тот, с которым мы хотим временно поработать
            case ($this->getID() != $id && $id):
                $obj = clone $this;
                $obj->edit($id);
                break;
            //Если уже загружен объект, с которым мы хотим временно поработать
            case ($this->getID() == $id && $id):
                //Если $id не указан, но уже загружен какой-то объект
            case (! $id && null !== $this->getID()):
            default:
                $obj = $this;
                break;
        }

        return $obj;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function useIgnore($flag = true)
    {
        $this->ignoreError = $flag ? 'IGNORE' : '';

        return $this;
    }

    /**
     * @return bool
     */
    public function hasIgnore()
    {
        return (bool)$this->ignoreError;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        if ((is_scalar($value) || $this->isJsonField($key)) && is_scalar($key) && ! empty($key)) {
            $this->field[$key] = $value;
        }

        return $this;
    }

    /**
     * @return null|int
     */
    final public function getID()
    {
        return $this->id;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return APIHelpers::getkey($this->field, $key, null);
    }

    /**
     * @param $data
     * @return $this
     */
    public function fromArray($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Формирует массив значений для подстановки в SQL запрос на обновление
     *
     * @param $key
     * @param string $id
     * @return $this
     * @throws Exception
     */
    final protected function Uset($key, $id = '')
    {
        if (! isset($this->field[$key])) {
            $tmp = "`{$key}`=''";
            $this->log[] = "{$key} is empty";
        } else {
            if ($this->issetField($key) && is_scalar($this->field[$key])) {
                $tmp = "`{$key}`='{$this->escape($this->field[$key])}'";
            } else {
                throw new Exception("{$key} is invalid <pre>" . print_r($this->field[$key], true) . "</pre>");
            }
        }
        if (! empty($tmp) && $this->isChanged($key)) {
            if ($id == '') {
                $this->set[] = $tmp;
            } else {
                $this->set[$id][] = $tmp;
            }
        }

        return $this;
    }

    /**
     * Сохраняет начальные значения полей
     *
     * @param array $data
     * @return $this
     */
    public function store($data = array())
    {
        if (is_array($data)) {
            $this->store = $data;
        }

        return $this;
    }

    /**
     * Откатывает изменения отдельного поля или всех полей сразу
     *
     * @param string $key
     * @return MODxAPI
     */
    public function rollback($key = '')
    {
        if (! empty($key) && isset($this->store[$key])) {
            $this->set($key, $this->store[$key]);
        } else {
            $this->fromArray($this->store);
        }

        return $this;
    }

    /**
     * Проверяет изменилось ли поле
     *
     * @param $key
     * @return bool
     */
    public function isChanged($key)
    {
        $flag = ! isset($this->store[$key]) || (isset($this->store[$key], $this->field[$key]) && $this->store[$key] != $this->field[$key]);

        return $flag;
    }

    /**
     * @param $IDs
     * @param string $sep
     * @param integer[] $ignore
     * @return array
     * @throws Exception
     */
    final public function cleanIDs($IDs, $sep = ',', $ignore = array())
    {
        $out = APIhelpers::cleanIDs($IDs, $sep, $ignore);

        return $out;
    }

    /**
     * @param $data
     * @param null $callback
     * @return $this
     * @throws Exception
     */
    final public function fromJson($data, $callback = null)
    {
        if (is_scalar($data) && ! empty($data)) {
            $json = json_decode($data);
        } else {
            throw new Exception("json is not string with json data");
        }

        if ($this->jsonError($json)) {
            if (isset($callback) && is_callable($callback)) {
                call_user_func_array($callback, array($json));
            } else {
                if (isset($callback)) {
                    throw new Exception("Can't call callback JSON unpack <pre>" . print_r($callback, 1) . "</pre>");
                }
                foreach ($json as $key => $val) {
                    $this->set($key, $val);
                }
            }
        } else {
            throw new Exception('Error from JSON decode: <pre>' . print_r($data, 1) . '</pre>');
        }

        return $this;
    }

    /**
     * @param null $callback
     * @return string
     * @throws Exception
     */
    final public function toJson($callback = null)
    {
        $data = $this->toArray();
        if (isset($callback) && is_callable($callback)) {
            $data = call_user_func_array($callback, array($data));
        } else {
            if (isset($callback)) {
                throw new Exception("Can't call callback JSON pre pack <pre>" . print_r($callback, 1) . "</pre>");
            }
        }
        $json = json_encode($data);

        if ($this->jsonError($data)) {
            throw new Exception('Error from JSON decode: <pre>' . print_r($data, 1) . '</pre>');
        }

        return $json;
    }

    /**
     * @param $data
     * @return bool
     */
    final protected function jsonError($data)
    {
        $flag = false;
        if (json_last_error() === JSON_ERROR_NONE && is_object($data) && $data instanceof stdClass) {
            $flag = true;
        }

        return $flag;
    }

    /**
     * @param string $prefix
     * @param string $suffix
     * @param string $sep
     * @return array
     */
    public function toArray($prefix = '', $suffix = '', $sep = '_')
    {
        $tpl = '';
        $plh = '[+key+]';
        if ($prefix !== '') {
            $tpl = $prefix . $sep;
        }
        $tpl .= $plh;
        if ($suffix !== '') {
            $tpl .= $sep . $suffix;
        }
        $out = array();
        $fields = $this->field;
        $fields[$this->fieldPKName()] = $this->getID();
        if ($tpl !== $plh) {
            foreach ($fields as $key => $value) {
                $out[str_replace($plh, $key, $tpl)] = $value;
            }
        } else {
            $out = $fields;
        }

        return $out;
    }

    /**
     * @return string
     */
    final public function fieldPKName()
    {
        return $this->pkName;
    }

    /**
     * @param $table
     * @return mixed|string
     */
    final public function makeTable($table)
    {
        //Без использования APIHelpers::getkey(). Иначе getFullTableName будет всегда выполняться
        return (isset($this->_table[$table])) ? $this->_table[$table] : $this->modx->getFullTableName($table);
    }

    /**
     * @param $data
     * @param string $sep
     * @return array|string
     */
    final public function sanitarIn($data, $sep = ',')
    {
        if (! is_array($data)) {
            $data = explode($sep, $data);
        }
        $out = array();
        foreach ($data as $item) {
            if ($item !== '') {
                $out[] = $this->escape($item);
            }
        }
        $out = empty($out) ? '' : "'" . implode("','", $out) . "'";

        return $out;
    }

    /**
     * @param string $table
     * @param string $field
     * @param string $PK
     * @return bool
     */
    public function checkUnique($table, $field, $PK = 'id')
    {
        if (is_array($field)) {
            $where = array();
            foreach ($field as $_field) {
                $val = $this->get($_field);
                if ($val != '') {
                    $where[] = "`" . $this->escape($_field) . "` = '" . $this->escape($val) . "'";
                }
            }
            $where = implode(' AND ', $where);
        } else {
            $where = '';
            $val = $this->get($field);
            if ($val != '') {
                $where = "`" . $this->escape($field) . "` = '" . $this->escape($val) . "'";
            }
        }

        if ($where != '') {
            $sql = $this->query("SELECT `" . $this->escape($PK) . "` FROM " . $this->makeTable($table) . " WHERE " . $where);
            $id = $this->modx->db->getValue($sql);
            $flag = (! $id || (! $this->newDoc && $id == $this->getID()));
        } else {
            $flag = false;
        }

        return $flag;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function create($data = array())
    {
        $this->close();
        $this->fromArray($data);

        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function copy($id)
    {
        $this->edit($id)->id = 0;
        $this->newDoc = true;
        $this->store = array();

        return $this;
    }

    /**
     *
     */
    public function close()
    {
        $this->newDoc = true;
        $this->id = null;
        $this->field = array();
        $this->set = array();
        $this->store = array();
        $this->markAllDecode();
    }

    /**
     * @param $key
     * @return bool
     */
    public function issetField($key)
    {
        return (is_scalar($key) && array_key_exists($key, $this->default_field));
    }

    /**
     * @param $id
     * @return mixed
     */
    abstract public function edit($id);

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return mixed
     */
    abstract public function save($fire_events = false, $clearCache = false);

    /**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    abstract public function delete($ids, $fire_events = null);

    /**
     * @param $data
     * @return array|mixed|string
     */
    final public function sanitarTag($data)
    {
        return parent::sanitarTag($this->modx->stripTags($data));
    }

    /**
     * @param string $version
     * @param bool $dmi3yy
     * @return bool
     */
    final protected function checkVersion($version, $dmi3yy = true)
    {
        $flag = false;
        $currentVer = $this->modx->getVersionData('version');
        if (is_array($currentVer)) {
            $currentVer = APIHelpers::getkey($currentVer, 'version', '');
        }
        $tmp = substr($currentVer, 0, strlen($version));
        if (version_compare($tmp, $version, '>=')) {
            $flag = true;
            if ($dmi3yy) {
                $flag = $flag || (boolean)preg_match('/^' . $tmp . '(.*)\-d/', $currentVer);
            }
        }

        return $flag;
    }

    /**
     * @param string $name
     * @return bool|string|int
     */
    protected function eraseField($name)
    {
        $flag = false;
        if (array_key_exists($name, $this->field)) {
            $flag = $this->field[$name];
            unset($this->field[$name]);
        }

        return $flag;
    }

    /**
     * Может ли содержать данное поле json массив
     * @param  string $field имя поля
     * @return boolean
     */
    public function isJsonField($field)
    {
        return (is_scalar($field) && in_array($field, $this->jsonFields));
    }

    /**
     * Пометить поле как распакованное
     * @param  string $field имя поля
     * @return $this
     */
    public function markAsDecode($field)
    {
        if (is_scalar($field)) {
            $this->_decodedFields->set($field, false);
        }

        return $this;
    }

    /**
     * Пометить поле как запакованное
     * @param  string $field имя поля
     * @return $this
     */
    public function markAsEncode($field)
    {
        if (is_scalar($field)) {
            $this->_decodedFields->set($field, true);
        }

        return $this;
    }

    /**
     * Пометить все поля как запакованные
     * @return $this
     */
    public function markAllEncode()
    {
        $this->_decodedFields->clear();
        foreach ($this->jsonFields as $field) {
            $this->markAsEncode($field);
        }

        return $this;
    }

    /**
     * Пометить все поля как распакованные
     * @return $this
     */
    public function markAllDecode()
    {
        $this->_decodedFields->clear();
        foreach ($this->jsonFields as $field) {
            $this->markAsDecode($field);
        }

        return $this;
    }

    /**
     * Получить список не запакованных полей
     * @return DLCollection
     */
    public function getNoEncodeFields()
    {
        return $this->_decodedFields->filter(function ($value) {
            return ($value === false);
        });
    }

    /**
     * Получить список не распакованных полей
     * @return DLCollection
     */
    public function getNoDecodeFields()
    {
        return $this->_decodedFields->filter(function ($value) {
            return ($value === true);
        });
    }

    /**
     * Можно ли данное декодировать с помощью json_decode
     * @param  string $field имя поля
     * @return boolean
     */
    public function isDecodableField($field)
    {
        $data = $this->get($field);

        /**
         * Если поле скалярного типа и оно не распаковывалось раньше
         */

        return (is_scalar($data) && is_scalar($field) && $this->_decodedFields->get($field) === true);
    }

    /**
     * Можно ли закодировать данные с помощью json_encode
     * @param  string $field имя поля
     * @return boolean
     */
    public function isEncodableField($field)
    {
        /**
         * Если поле было распаковано ранее и еще не упаковано
         */
        return (is_scalar($field) && $this->_decodedFields->get($field) === false);
    }

    /**
     * Декодирует конкретное поле
     * @param  string $field Имя поля
     * @param  bool $store обновить распакованное поле
     * @return array ассоциативный массив с данными из json строки
     */
    public function decodeField($field, $store = false)
    {
        $out = array();
        if ($this->isDecodableField($field)) {
            $data = $this->get($field);
            $out = jsonHelper::jsonDecode($data, array('assoc' => true), true);
        }
        if ($store) {
            $this->field[$field] = $out;
            $this->markAsDecode($field);
        }

        return $out;
    }

    /**
     * Декодирование всех json полей
     * @return $this
     */
    protected function decodeFields()
    {
        foreach ($this->getNoDecodeFields() as $field => $flag) {
            $this->decodeField($field, true);
        }

        return $this;
    }

    /**
     * Запаковывает конкретное поле в JSON
     * @param  string $field Имя поля
     * @param  bool $store обновить запакованное поле
     * @return string|null json строка
     */
    public function encodeField($field, $store = false)
    {
        $out = null;
        if ($this->isEncodableField($field)) {
            $data = $this->get($field);
            $out = json_encode($data);
        }
        if ($store) {
            $this->field[$field] = $out;
            $this->markAsEncode($field);
        }

        return $out;
    }

    /**
     * Запаковка всех json полей
     * @return $this
     */
    protected function encodeFields()
    {
        foreach ($this->getNoEncodeFields() as $field => $flag) {
            $this->encodeField($field, true);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * @param string $key
     * @return bool
     */
    protected function saveToCache($data, $key)
    {
        $out = false;
        if ($this->cache) {
            $out = $this->modx->cache->save($key, $data, 0);
        }

        return $out;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadFromCache($key)
    {
        $out = false;
        if ($this->cache) {
            $out = $this->modx->cache->fetch($key);
        }

        return $out;
    }

}
