<?php namespace Module;

/**
 * Class Action
 * @package Module
 */
abstract class Action
{
    /**
     * Объект DocumentParser - основной класс MODX
     * @var \EvolutionCMS\Core
     * @access protected
     */
    protected static $modx = null;
    public static $TPL = null;
    protected static $TABLE = "site_content";
    protected static $classTable = null;
    protected static $_tplObj = null;

    /**
     * @param \EvolutionCMS\Core $modx
     */
    public static function setMODX(\EvolutionCMS\Core $modx)
    {
        self::$modx = $modx;
    }

    /**
     * @param \EvolutionCMS\Core $modx
     * @param Template $tpl
     * @param \MODxAPI $classTable
     */
    public static function init(\EvolutionCMS\Core $modx, Template $tpl, \MODxAPI $classTable)
    {
        self::setMODX($modx);
        self::$TPL = Template::showLog();
        self::$_tplObj = $tpl;
        self::$classTable = $classTable;
    }

    /**
     * @return string
     */
    public static function TABLE()
    {
        return static::$TABLE;
    }

    /**
     * @param $id
     * @return bool
     */
    protected static function _checkObj($id)
    {
        $q = self::$modx->getDatabase()->select('id', self::$modx->getDatabase()->getFullTableName(self::TABLE()), "id = " . $id);

        return (self::$modx->getDatabase()->getRecordCount($q) == 1);
    }

    /**
     * @param $field
     * @param $id
     * @return mixed
     */
    protected static function _getValue($field, $id)
    {
        $q = self::$modx->getDatabase()->select($field, self::$modx->getDatabase()->getFullTableName(self::TABLE()), "id = " . $id);

        return self::$modx->getDatabase()->getValue($q);
    }

    /**
     * @return array|mixed
     */
    public static function listValue()
    {
        $out = self::_workValue(function ($data, $modObj) {
            $listFunction = $data['key'] . 'Lists';
            $out = method_exists($modObj, $listFunction) ? $modObj->$listFunction() : array();
            $out['selected'] = $modObj->get($data['key']);

            return $out;
        });
        self::$TPL = null;

        return $out;
    }

    /**
     * @param $callback
     * @return array|mixed
     */
    protected static function _workValue($callback)
    {
        self::$TPL = 'ajax/getValue';
        $data = Helper::jeditable('data');
        $out = array();
        if (!empty($data)) {
            $modObj = self::$classTable;
            $modObj->edit($data['id']);
            if ($modObj->getID() !== null && ((is_object($callback) && ($callback instanceof \Closure)) || is_callable($callback))) {
                $out = call_user_func($callback, $data, $modObj);
            }
        }

        return $out;
    }

    /**
     * @return array|mixed
     */
    public static function saveValue()
    {
        return self::_workValue(function ($data, $modObj) {
            $out = array();
            if (isset($_POST['value']) && is_scalar($_POST['value'])) {
                if ($modObj->set($data['key'], $_POST['value'])->save()) {
                    $textMethod = $data['key'] . 'Text';
                    if (method_exists($modObj, $textMethod)) {
                        $out['value'] = $modObj->$textMethod();
                    } else {
                        $out['value'] = $modObj->get($data['key']);
                    }
                }
            }

            return $out;
        });
    }

    /**
     * @return array|mixed
     */
    public static function getValue()
    {
        return self::_workValue(function ($data, $modObj) {
            return array(
                'value' => $modObj->get($data['key'])
            );
        });
    }

    /**
     * @return array
     */
    public static function deleted()
    {
        $data = array();
        $dataID = (int)Template::getParam('docId', $_GET);
        if ($dataID > 0 && self::_checkObj($dataID)) {
            $oldValue = self::_getValue('deleted_at', $dataID);
            $q = self::$modx->getDatabase()->update(array(
                'deleted_at' => empty($oldValue) ? date('Y-m-d H:i:s') : null
            ), self::$modx->getDatabase()->getFullTableName(self::TABLE()), "id = " . $dataID);
            if ($q) {
                $data['log'] = $oldValue ? 'Запись с ID ' . $dataID . ' восстановлена' : 'Запись с ID ' . $dataID . ' удалена';
            } else {
                $data['log'] = $oldValue ? 'Не удалось восстановить запись с ID ' . $dataID : 'Не удалось удалить запись с ID ' . $dataID;
            }
        } else {
            $data['log'] = '<span class="error">Ошибка</span>. Не удалось определить обновляему запись';
        }

        return $data;
    }

    /**
     *
     */
    public static function lists()
    {
        self::$TPL = 'ajax/lists';
    }

    /**
     * @return null
     */
    public static function getClassTable()
    {
        return self::$classTable;
    }

}
