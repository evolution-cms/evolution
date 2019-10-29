<?php namespace Helpers;

use APIhelpers;
use DocumentParser;
use Helpers\Lexicon\AbstractLexiconHandler;

/**
 * Class Lexicon
 * @package Helpers
 */
class Lexicon
{
    protected $modx = null;
    public $config = null;
    protected $lexicon = array();
    protected $lexiconHandler = null;
    protected $aliases = array(
        'bg' => 'bulgarian',
        'zh' => 'chinese',
        'cs' => 'czech',
        'da' => 'danish',
        'en' => 'english',
        'fi' => 'finnish',
        'fr' => 'francais-utf8',
        'de' => 'german',
        'he' => 'hebrew',
        'it' => 'italian',
        'jp' => 'japanese-utf8',
        'nl' => 'nederlands-utf8',
        'no' => 'norsk',
        'fa' => 'persian',
        'pl' => 'polish-utf8',
        'pt' => 'portuguese-br-utf8',
        'ru' => 'russian-UTF8',
        'es' => 'spanish-utf8',
        'sv' => 'svenska-utf8',
        'uk' => 'ukrainian'
    );

    /**
     * Lexicon constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct (DocumentParser $modx, $cfg = array())
    {
        $this->modx = $modx;
        $this->config = new Config($cfg);
        $handler = $this->config->getCFGDef('handler', 'Helpers\\Lexicon\\EvoBabelLexiconHandler');
        if (class_exists($handler)) {
            $handler = new $handler($modx, $this);
            if ($handler instanceof AbstractLexiconHandler) {
                $this->lexiconHandler = $handler;
            }
        }
    }

    /**
     * Загрузка языкового пакета
     *
     * @param string $name файл языкового пакета
     * @param string $lang имя языкового пакета
     * @param string $langDir папка с языковыми пакетами
     * @return array массив с лексиконом
     */
    public function fromFile ($name = 'core', $lang = '', $langDir = '')
    {
        $langDir = empty($langDir) ? MODX_BASE_PATH . $this->config->getCFGDef('langDir',
                'lang/') : MODX_BASE_PATH . $langDir;
        if (empty($lang)) {
            $lang = $this->config->getCFGDef('lang', $this->modx->getConfig('lang_code'));
        }

        if (is_scalar($name) && !empty($name)) {
            $name = array($name);
        }

        foreach ($name as $n) {
            if ($lang != 'english' && $lang != 'en') {
                $this->loadLexiconFile($n, 'en', $langDir);
            }
            $this->loadLexiconFile($n, $lang, $langDir);
        }

        return $this->getLexicon();
    }

    /**
     * Загрузка языкового пакета
     *
     * @param string $name файл языкового пакета
     * @param string $lang имя языкового пакета
     * @param string $langDir папка с языковыми пакетами
     * @return array массив с лексиконом
     * @deprecated
     */
    public function loadLang ($name = 'core', $lang = '', $langDir = '')
    {
        return $this->fromFile($name, $lang, $langDir);
    }

    /**
     * @param string $name
     * @param string $lang
     * @param string $langDir
     */
    private function loadLexiconFile ($name = 'core', $lang = '', $langDir = '')
    {
        $filepath = "{$langDir}{$lang}/{$name}.inc.php";
        if (!file_exists($filepath)) {
            $filepath = "{$langDir}{$this->getAlias($lang)}/{$name}.inc.php";
        }
        if (file_exists($filepath)) {
            $tmp = include($filepath);
            if (is_array($tmp)) {
                $this->setLexicon($tmp);
            }
        }
    }

    /**
     * Получение строк из массива
     *
     * @param $lang
     * @return array
     */
    public function fromArray ($lang = array())
    {
        $language = $this->config->getCFGDef('lang', $this->modx->getConfig('manager_language'));
        if(is_array($lang) && !isset($lang[$language])) {
            $language = $this->getAlias($language);
        }
        if (is_array($lang) && isset($lang[$language])) {
            $this->setLexicon($lang[$language]);
        }

        return $this->getLexicon();
    }

    /**
     * Получение строки из языкового пакета
     *
     * @param string $key имя записи в языковом пакете
     * @param string $default Строка по умолчанию, если запись в языковом пакете не будет обнаружена
     * @return string строка в соответствии с текущими языковыми настройками
     */
    public function get ($key, $default = '')
    {
        $out = APIhelpers::getkey($this->lexicon, $key, $default);
        if (!is_null($this->lexiconHandler)) {
            $out = $this->lexiconHandler->get($key, $out);
        }

        return $out;
    }

    /**
     * Получение строки из языкового пакета
     *
     * @param string $key имя записи в языковом пакете
     * @param string $def Строка по умолчанию, если запись в языковом пакете не будет обнаружена
     * @return string строка в соответствии с текущими языковыми настройками
     * @deprecated
     */
    public function getMsg ($key, $def = '')
    {
        return $this->get($key, $def);
    }

    /**
     * @param $tpl
     * @return string
     */
    public function parse ($tpl)
    {
        if (is_scalar($tpl) && !empty($tpl)) {
            if (preg_match_all("/\[\%([a-zA-Z0-9\.\_\-]+)\%\]/", $tpl, $match)) {
                $langVal = array();
                 foreach ($match[1] as $item) {
                    $langVal[] = $this->get($item);
                }
                $tpl = str_replace($match[0], $langVal, $tpl);
            }
        } else {
            $tpl = '';
        }

        return $tpl;
    }

    /**
     * Замена в шаблоне фраз из лексикона
     *
     * @param string $tpl HTML шаблон
     * @return string
     * @deprecated
     */
    public function parseLang ($tpl)
    {
        return $this->parse($tpl);
    }

    /**
     * @return bool
     */
    public function isReady ()
    {
        return !empty($this->lexicon);
    }

    /**
     * @param array $lexicon
     * @param bool $overwrite
     * @return $this
     */
    public function setLexicon ($lexicon = array(), $overwrite = false)
    {
        if ($overwrite) {
            $this->lexicon = $lexicon;
        } else {
            $this->lexicon = array_merge($this->lexicon, $lexicon);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLexicon ()
    {
        return $this->lexicon;
    }

    /**
     * @param $language string
     * @return string
     */
    public function getAlias($language) {
        if (isset($this->aliases[$language])) {
            $language = $this->aliases[$language];
        }

        return $language;
    }
}
