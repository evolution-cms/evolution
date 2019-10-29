<?php namespace Helpers;

include_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
require_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/jsonHelper.class.php");

use jsonHelper;
use APIhelpers;

class Config
{
    private $_cfg = array();
    /** @var FS */
    protected $fs;
    protected $path = '';

    /**
     * Config constructor.
     *
     * @param array $cfg
     */
    public function __construct($cfg = array())
    {
        if (! empty($cfg)) {
            $this->setConfig($cfg);
        }
        $this->fs = FS::getInstance();
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Загрузка конфигов из файла
     *
     * @param $name string имя конфига
     * @return array массив с настройками
     */
    public function loadConfig($name)
    {
        //$this->debug->debug('Load json config: ' . $this->debug->dumpData($name), 'loadconfig', 2);
        if (!is_scalar($name)) {
            $name = '';
        }
        $config = array();
        $name = explode(";", $name);
        foreach ($name as $cfgName) {
            $cfgName = explode(":", $cfgName, 2);
            if (empty($cfgName[1])) {
                $cfgName[1] = 'custom';
            }
            $cfgName[1] = rtrim($cfgName[1], '/');
            switch ($cfgName[1]) {
                case 'custom':
                case 'core':
                    $configFile = $this->fs->relativePath($this->path) . "/config/{$cfgName[1]}/{$cfgName[0]}.json";
                    break;
                default:
                    $configFile = $this->fs->relativePath($cfgName[1] . '/' . $cfgName[0] . ".json");
                    break;
            }

            if ($this->fs->checkFile($configFile)) {
                $json = file_get_contents(MODX_BASE_PATH . $configFile);
                /** @var array $json */
                $json = jsonHelper::jsonDecode($json, array('assoc' => true), true);
                $config = array_merge($config, $json);
            }
        }

        $this->setConfig($config);

        return $config;
    }

    /**
     * Получение всего списка настроек
     * @return array
     */
    public function getConfig()
    {
        return $this->_cfg;
    }

    /**
     * Сохранение массива настроек
     * @param array $cfg массив настроек
     * @return int|bool результат сохранения настроек
     */
    public function setConfig($cfg, $overwrite = false)
    {
        if (is_array($cfg)) {
            $this->_cfg = $overwrite ? $cfg : array_merge($this->_cfg, $cfg);
            $ret = count($this->_cfg);
        } else {
            $ret = false;
        }

        return $ret;
    }

    /**
     * @param $name
     * @param mixed $def
     * @return mixed
     */
    public function getCFGDef($name, $def = null)
    {
        return APIhelpers::getkey($this->_cfg, $name, $def);
    }

    /**
     * Преобразует json или строку с разделителем в массив.
     *
     * @param $arr
     * @param string $sep
     * @return array|mixed|\xNop
     */
    public function loadArray($arr, $sep = ',')
    {
        switch (gettype($arr)) {
            case 'string':
                $out = jsonHelper::jsonDecode($arr, array('assoc' => true));
                if (!is_array($out)) {
                    $out = $sep ? array_filter(explode($sep, $arr)) : array();
                }
                break;
            case 'array':
                $out = $arr;
                break;
            case 'object':
                $out = array($arr);
                break;
            default:
                $out = array();
        }
        return $out;
    }
}
