<?php
require_once('MODx.php');

/**
 * Class autoTable
 */
abstract class autoTable extends MODxAPI
{
    /**
     * @var null
     */
    protected $table = null;
    /**
     * @var bool
     */
    protected $generateField = false;

    /**
     * @return null
     */
    public function tableName()
    {
        return $this->table;
    }

    /**
     * autoTable constructor.
     * @param DocumentParser $modx
     * @param bool $debug
     */
    public function __construct($modx, $debug = false)
    {
        parent::__construct($modx, $debug);
        if (empty($this->default_field)) {
            $data = $this->modx->db->getTableMetaData($this->makeTable($this->table));
            foreach ($data as $item) {
                if (empty($this->pkName) && $item['Key'] == 'PRI') {
                    $this->pkName = $item['Field'];
                }
                if ($this->pkName != $item['Field']) {
                    $this->default_field[$item['Field']] = $item['Default'];
                }
            }
            $this->generateField = true;
        }
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
            $this->markAllEncode();
            $this->newDoc = false;
            $result = $this->query("SELECT * from {$this->makeTable($this->table)} where `" . $this->pkName . "`='" . $this->escape($id) . "'");
            $this->fromArray($this->modx->db->getRow($result));
            $this->store($this->toArray());
            $this->id = $this->eraseField($this->pkName);
            if (is_bool($this->id) && $this->id === false) {
                $this->id = null;
            } else {
                $this->decodeFields();
            }
        }

        return $this;
    }

    /**
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool|null|void
     */
    public function save($fire_events = false, $clearCache = false)
    {
        foreach ($this->jsonFields as $field) {
            if ($this->get($field) === null
                && isset($this->default_field[$field])
                && is_array($this->default_field[$field]))
            {
                $this->set($field, $this->default_field[$field]);
            }
        }
        $fld = $this->encodeFields()->toArray();
        foreach ($this->default_field as $key => $value) {
            if ($this->newDoc && $this->get($key) === null && $this->get($key) !== $value) {
                $this->set($key, $value);
            }
            if ((! $this->generateField || isset($fld[$key])) && $this->get($key) !== null) {
                $this->Uset($key);
            }
            unset($fld[$key]);
        }
        if (! empty($this->set)) {
            if ($this->newDoc) {
                $SQL = "INSERT {$this->ignoreError} INTO {$this->makeTable($this->table)} SET " . implode(', ',
                        $this->set);
            } else {
                $SQL = ($this->getID() === null) ? null : "UPDATE {$this->ignoreError} {$this->makeTable($this->table)} SET " . implode(', ',
                        $this->set) . " WHERE `" . $this->pkName . "` = " . $this->getID();
            }
            $this->query($SQL);
            if ($this->newDoc) {
                $this->id = $this->modx->db->getInsertId();
            }
        }
        if ($clearCache) {
            $this->clearCache($fire_events);
        }
        $this->decodeFields();

        return $this->id;
    }

    /**
     * @param $ids
     * @param bool $fire_events
     * @return $this
     * @throws Exception
     */
    public function delete($ids, $fire_events = false)
    {
        $_ids = $this->cleanIDs($ids, ',');
        if (is_array($_ids) && $_ids !== array()) {
            $id = $this->sanitarIn($_ids);
            if (! empty($id)) {
                $this->query("DELETE from {$this->makeTable($this->table)} where `" . $this->pkName . "` IN ({$id})");
            }
        } else {
            throw new Exception('Invalid IDs list for delete: <pre>' . print_r($ids, 1) . '</pre>');
        }

        return $this;
    }
}
